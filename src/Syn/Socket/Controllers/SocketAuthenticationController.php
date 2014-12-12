<?php namespace Syn\Socket\Controllers;

use App;
use Auth;
use Config;
use Illuminate\Support\Collection;
use Input;
use Response;
use Syn\Framework\Abstracts\Controller;
use Syn\Socket\Classes\Channel;
use Syn\Socket\Classes\Pusher;

class SocketAuthenticationController extends Controller
{
	const DELIMITER = ';';

	public function authenticate()
	{
		if(!Auth::check())
			App::abort(403);
		$channels = Input::get('channel_name', []);
		$gamer = App::make('Visitor');
		$socket_id = Input::get('socket_id');

		$channelOutput = [];
		// work on presence channels
		foreach($channels as $channel)
		{
			$authorized = false;
			if(preg_match('/^presence-(.*)/', $channel, $m))
			{
				$chan = $m[1] . static::DELIMITER . static::DELIMITER;
				$m = explode(static::DELIMITER, $chan);
				$type = array_get($m, 0);
				$id = array_get($m, 1);
				$sub = array_get($m, 2);
	//			\dd($channel,$m,$type,$id,$sub);
				switch($type)
				{
					case 'admin':
						if($gamer->admin)
							$authorized = true;
						break;
					case 'user':
						// private channel user
						if($id && $id == $gamer -> id)
							$authorized = true;
						// all users channel list
						elseif(!$id && Auth::check())
							$authorized = true;
						break;
					case 'clan':
						// membership of clan
						if($id && !$sub && $gamer->membershipOf($id))
							$authorized = true;
						elseif($id && $sub == 'leader' && $gamer->membershipOf($id) && $gamer->membershipOf($id)->leader)
							$authorized = true;
						elseif($id && $sub == 'admin' && $gamer->membershipOf($id) && $gamer->membershipOf($id)->allow_adminning)
							$authorized = true;
						break;

				}
				if($authorized)
				{
					$channelOutput[$channel] = [
						'status' => 200,
						'data' => json_decode(Pusher::presence_auth($channel, $socket_id, $gamer -> id, $gamer -> toArray()))
					];
				}

			}
			if(!$authorized)
				$channelOutput[$channel] = [
					'status' => 403
				];
		}
		return Response::json($channelOutput);
	}

	/**
	 * Loads all channels this user can be assigned to
	 *
	 * @return array
	 */
	public function channels()
	{
		if(!Auth::check())
			App::abort(403);


		$gamer = App::make('Visitor');

		$channels = new Collection();

		$channels -> put('user', Channel::generate("presence" , "user", $gamer->id));
		$channels -> put('users', Channel::generate("presence", "user"));
		foreach($gamer->memberships as $membership)
		{
			// clan membership
			$channels -> put("clan:{$membership->clan_id}", Channel::generate('presence', 'clan', $membership->clan_id));

			// clan leader channel
			if($membership->title->leader)
				$channels -> put("clan:{$membership->clan_id}:leader", Channel::generate('presence', 'clan', $membership->clan_id, 'leader'));
			// clan cup management channel
			if($membership->title->allow_adminning)
				$channels -> put("clan:{$membership->clan_id}:admin", Channel::generate('presence', 'clan', $membership->clan_id, 'admin'));
		}

		foreach($gamer->runningTeamMemberships as $membership)
		{
			// currently active cups channel
			$channels -> put("cup:{$membership->team->cup_id}", Channel::generate('presence', 'cup', $membership->cup_id));
		}

		if($gamer->admin)
			$channels -> put("admin", Channel::generate('presence', 'admin'));

		return Response::json($channels);
	}
}