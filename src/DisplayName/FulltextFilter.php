<?php

namespace AlexanderOMara\FlarumWPUsers\DisplayName;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\User\UserRepository;
use Flarum\User\LoginProvider;
use Illuminate\Database\Eloquent\Builder;

use AlexanderOMara\FlarumWPUsers\Core;

/**
 * FulltextFilter class.
 *
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class FulltextFilter extends AbstractFulltextFilter {
	/**
	 * User repository.
	 *
	 * @var UserRepository
	 */
	protected UserRepository $users;

	/**
	 * Core object.
	 *
	 * @var Core
	 */
	protected Core $core;

	/**
	 * FulltextFilter class.
	 *
	 * @param UserRepository User repository.
	 * @param Core Core object.
	 */
	public function __construct(UserRepository $users, Core $core) {
		$this->users = $users;
		$this->core = $core;
	}

	/**
	 * Get user WordPress sub-query.
	 *
	 * @param string $value The search string.
	 * @return Builder|null Builder instance or null if none.
	 */
	protected function getUserWordPressSubQuery(string $value): ?Builder {
		// Search for matching WordPress display names, if any.
		$wpUsers = $this->core->displayNameSearch($value);
		if (!$wpUsers) {
			return null;
		}
		return LoginProvider::query()->select('user_id')
			->where('provider', Core::ID)
			->whereIn('identifier', $wpUsers);
	}

	/**
	 * Get user search sub-query.
	 *
	 * @param string $value The search string.
	 * @return Builder Builder instance.
	 */
	protected function getUserSearchSubQuery(string $value): Builder {
		// Base query used in:
		// Flarum\User\Search\FulltextFilter->search
		$query = $this->users->query()->select('id');
		$pattern = "%{$value}%";
		switch ($query->getConnection()->getDriverName()) {
			case 'pgsql': {
				$query->where('username', 'ilike', $pattern);
				break;
			}
			case 'sqlite': {
				$query->whereRaw('LOWER(username) LIKE ?', [
					mb_strtolower($pattern)
				]);
				break;
			}
			default: {
				$query->where('username', 'like', $pattern);
			}
		}

		// If display name driver is enabled, extend query.
		if ($this->core->displayNameDriverEnabled()) {
			$subQuery = $this->getUserWordPressSubQuery($value);
			if ($subQuery) {
				$query->orWhereIn('id', $subQuery);
			}
		}
		return $query;
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(SearchState $state, string $value): void {
		$state->getQuery()->whereIn('id', $this->getUserSearchSubQuery($value));

		// Same default sort as core, usernames that start with value first.
		$state->setDefaultSort(function (Builder $query) use ($value) {
			$username = $query->getGrammar()->wrap('users.username');
			$query
				->orderByRaw(
					"CASE WHEN LOWER({$username}) LIKE ? THEN 0 ELSE 1 END",
					[mb_strtolower($value) . '%']
				)
				->orderBy('users.username');
		});
	}
}
