<?php
Route::group(['namespace' => 'Syn\Socket\Controllers'], function()
{
	Route::group([
		'before' 	=> ['auth', 'csrf'],
	], function()
	{

		Route::any('/websocket/authenticate', [
			'as' => 'Socket@auth',
			'uses' => 'SocketAuthenticationController@authenticate'
		]);
		Route::post('/websocket/channels', [
			'as' => 'Socket@channels',
			'uses' => 'SocketAuthenticationController@channels'
		]);
	});
});