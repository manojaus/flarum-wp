<?php

use Flarum\Extend;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Event\Saving;
use Flarum\User\Search\UserSearcher;

use AlexanderOMara\FlarumWPUsers\Controller;
use AlexanderOMara\FlarumWPUsers\Core;
use AlexanderOMara\FlarumWPUsers\Events;
use AlexanderOMara\FlarumWPUsers\Listener;
use AlexanderOMara\FlarumWPUsers\Middleware;
use AlexanderOMara\FlarumWPUsers\DisplayName;
use AlexanderOMara\FlarumWPUsers\Provider;

return [
	// Register core class as a service (a singleton).
	(new Extend\ServiceProvider())
		->register(Provider\CoreProvider::class),

	// Client-side code.
	(new Extend\Frontend('forum'))
		->js(__DIR__ . '/js/dist/forum.js')
		->content(Listener\AddData::class),
	(new Extend\Frontend('admin'))
		->js(__DIR__ . '/js/dist/admin.js'),

	// Middleware.
	(new Extend\Middleware('forum'))
		->insertAfter(
			HttpMiddleware\AuthenticateWithSession::class,
			Middleware\Authenticate::class
		),
	(new Extend\Middleware('admin'))
		->insertAfter(
			HttpMiddleware\AuthenticateWithSession::class,
			Middleware\Authenticate::class
		),
	(new Extend\Middleware('api'))
		->insertAfter(
			HttpMiddleware\AuthenticateWithHeader::class,
			Middleware\Authenticate::class
		),

	// Events.
	(new Extend\Event())
		->listen(Saving::class, Events\UserSaving::class),

	// Routes.
	(new Extend\Routes('api'))
		->remove('forgot')
		->post(
			'/forgot',
			'forgot',
			Controller\ForgotPasswordController::class
		),
	(new Extend\Routes('forum'))
		->remove('register')
		->post(
			'/register',
			'register',
			Controller\RegisterController::class
		)
		->remove('logout')
		->post(
			'/logout',
			'logout',
			Controller\LogOutController::class
		),

	// Display name.
	(new Extend\User())
		->displayNameDriver(
			Core::DISPLAY_NAME_DRIVER,
			DisplayName\Driver::class
		),
	(new Extend\SearchDriver(DatabaseSearchDriver::class))
		->setFulltext(UserSearcher::class, DisplayName\FulltextFilter::class)
];
