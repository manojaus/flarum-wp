<?php

namespace AlexanderOMara\FlarumWPUsers\Controller;

use Flarum\Forum\Controller\LogOutController as Base;
use Flarum\Http\RequestUtil;
use Flarum\User\Event\LoggedOut;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Log out intercept controller.
 */
class LogOutController extends Base {
	/**
	 * Request handler.
	 *
	 * @param Request $request Request object.
	 * @return Response Response object.
	 */
	public function handle(Request $request): Response {
		// Ignore if guest or session has an access token.
		// A session authenticated by the WordPress cookie has no token.
		$session = $request->getAttribute('session');
		$actor = RequestUtil::getActor($request);
		if ($actor->isGuest() || $session->get('access_token') !== null) {
			return parent::handle($request);
		}

		// Based on the parent, without the token SessionAuthenticator needs.
		$return = $this->sanitizeReturnUrl(
			(string) Arr::get($request->getQueryParams(), 'return'),
			$this->url->to('forum')->base()
		);
		$session->invalidate();
		$session->regenerateToken();
		$this->events->dispatch(new LoggedOut($actor, false));
		return $this->rememberer->forget(new RedirectResponse($return));
	}
}
