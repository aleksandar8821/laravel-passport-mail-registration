<?php
namespace App\My_custom_files;

use App\UserAccessBlocking;
use App\Mail\SafeAccessStyled;
use Carbon\Carbon;

trait userAccessBlockingTrait
{

	public $userAccessBlocking;
	public $userBlocked;
	public $blockedUserAccessAllowed;
	
	// Ovu metodu koristim kad postoji sumnja da je useru neko zloupotrebio nalog, pa blokiram usera, ali mu saljem mail sa allow access tokenom da bi preko maila mogao da pristupi nalogu - tako da moze pristupiti samo originalni korisnik koji je vlasnik emaila, a ovaj koji mu zloupotrebljava nalog ne moze! Takodje logoutujem usera svugde gde je dosad bio ulogovan (radim revoke() na svim access tokenima u tabeli) da bi izlogovao i zlonamernog korisnika koji mu je nelegalno pristupio nalogu.
	public function blockUserWithSafeAccess($user, $hours_to_expire = null)
	{
		$blockUser = new UserAccessBlocking;

		$blockUser->user_id = $user->id;
		$allow_access_token = $this->get_clean_microtimestamp_string().str_random(30);
		$blockUser->allow_access_token = $allow_access_token;
		if ($hours_to_expire) {
			$blockUser->expires_at = now()->addHours($hours_to_expire);
		}

		$blockUser->save();

		// Brisem prethodno uneseno blokiranje usera iz tabele, jer bi trebalo uvek da imam samo jedno blokiranje aktivno za jednog usera, jer ako ih ima vise mogu dolaziti u konflikt
		UserAccessBlocking::where('user_id', $user->id)->where('id', '!=', $blockUser->id)->delete();

		// Hah, EKSTRA!!! Pomocu ovog $token->revoke(); sam ucinio sve dosadasnje logine datog usera nevalidnim (foru pokupio ovde https://stackoverflow.com/questions/42851676/how-to-invalidate-all-tokens-for-an-user-in-laravel-passport , slicne stvari se spominju i ovde pri kraju https://laracasts.com/discuss/channels/general-discussion/laravel-56-and-passport-how-to-logout . Pretpostavljam da se ovaj metod moze primeniti svugde gde je implementiran ovaj OAuth2 sistem za autentifikaciju, sa njim inace radi Laravel Passport kojeg ja ovde koristim. Vise o tome vidi ovdde: https://laravel.com/docs/5.5/passport#introduction , https://github.com/thephpleague/oauth2-server , https://oauth2.thephpleague.com/). Prakticno sam ga izlogovao svugde gde je ikad bio ulogovan! Ovo je jako dobra stvar sto se tice sigurnosti. (U NAREDNIM RECENICAMA OPISUJEM SLUCAJ ZA KOJI SAM KORISTIO OVU METODU PA I OVAJ REVOKE TOKENA, STO NE ZNACI DA SE SAMO U TOM SLUCAJU MOZE KORISTITI OVA METODA) Dakle kad user blokira i/ili opozove (revokeuje) promene pretpostavljajuci da ih je otimac naloga inicirao, ovim ce automatski i izlogovati otimaca naloga ako je negde ulogovan. Doduse i sam legalan vlasnik naloga ce biti izlogovan, ali ako hoce da izloguje i otimaca naloga cini mi se da mora ovako. Jer kako ja da znam koji je access token od otimaca naloga a koji je od legalnog korisnika. Otimac naloga moze da bude ulogovan i sa originalnim podacima legalnog korisnika, tako da svugde radim logout!
		$userTokens = $user->tokens;

		foreach($userTokens as $token) {
		    $token->revoke();   
		}

		\Mail::to($user)->send(new SafeAccessStyled($user->first_name, $allow_access_token));

	}

	// Ova metoda proverava sve opcije blokiranja usera. I kad postoji expires_at i kad ne postoji, i kad postoji allow_access_token i kad ne postoji (tacnije trebalo bi da bude primenjiva i u slucaju da administrator npr banuje usera, zatim kad korisnik sam blokira svoj nalog preko opcije koju mu dajem preko maila (ako sumnja da mu je neko zloupotrebljava nalog), kao i u drugim nekim slucajevima koji jos nisu implementirani u projektu). Za sad je sve radilo kako treba.
	public function check_if_user_is_blocked($request, $user)
	{

			if(!$user){
				return false;
			}

      $userAccessBlocking = UserAccessBlocking::where('user_id', $user->id)->first();

      if($userAccessBlocking){

      	$this->userAccessBlocking = $userAccessBlocking;

      	if($userAccessBlocking->expires_at){
      		if($userAccessBlocking->expires_at > now()){
      			$userBlocked = true;
      		}else{
      			$userBlocked = false;
      		}
      	}else{
      		// Ovo znaci da je expires_at NULL, sto znaci da je na snazi blokiranje koje usera blokira zauvek
      		$userBlocked = true;
      	}

      	$this->userBlocked = $userBlocked;

      }else{
      	return false;
      }

      if($userBlocked){
      		// Ovo je jedini uslov ukoliko je korisnik blokiran (i naravno ukoliko mu expires_at ako ga ima nije jos istekao, a te uslove sam vec proverio gore) da uspe da se uloguje, dakle da token nije u bazi null i da se tokeni iz requesta i baze poklapaju
      		if ($userAccessBlocking->allow_access_token && ($request->allow_access_token === $userAccessBlocking->allow_access_token)){

      			$this->blockedUserAccessAllowed = true;
      			return false;

      		}else{
      			if($userAccessBlocking->expires_at){
      			    $unblockPeriod = Carbon::now()->diffInHours(Carbon::createFromFormat('Y-m-d H:i:s', $userAccessBlocking->expires_at));
      			    
      			    if($unblockPeriod > 1) {
      			        return 'Your account is blocked, and you will not be able to access it for next '.$unblockPeriod.' hours!';
      			    }else{
      			        $unblockPeriod = Carbon::now()->diffInMinutes(Carbon::createFromFormat('Y-m-d H:i:s', $userAccessBlocking->expires_at));
      			        return 'Your account is blocked, and you will not be able to access it for next '.$unblockPeriod.' minutes!';
      			    }

      			}else{
      			    return 'Your account is blocked!';
      			}
      		}

          
      }else{
      	return false;
      }
	}



	
}
