<?php namespace Syn\Socket\Classes;

class Channel
{
	const DELIMITER = ';';



	/**
	 * Formats the channel
	 * @param string $channel_type
	 * @param        $type
	 * @param bool   $id
	 * @param bool   $sub
	 * @return string
	 */
	public static function generate($channel_type = 'presence', $type, $id = false, $sub = false)
	{
		$collection = [];
		$collection[] = $type;
		if($id)
			$collection[] = $id;
		if($sub)
			$collection[] = $sub;

		$compound = implode(static::DELIMITER, $collection);

		return sprintf("%s-%s", $channel_type, $compound);
	}

	public static function load($channel_type = 'presence', $type, $id = false, $sub = false)
	{
		return Pusher::get_channel_info(static::generate($channel_type, $type, $id, $sub));
	}
}