<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Backend;

use OC\User\LazyUser;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\IDBConnection;
use OCP\IUserManager;

class SystemGroupBackend extends ABackend implements
	IAddToGroupBackend,
	ICountUsersBackend,
	IGetDisplayNameBackend,
	IHideFromCollaborationBackend,
	IRemoveFromGroupBackend {

	private array $systemGroups = [
		'waiting-approval',
	];
	private ?IDBConnection $dbConn = null;

	/**
	 * \OC\Group\Database constructor.
	 *
	 * @param IDBConnection|null $dbConn
	 */
	public function __construct(IDBConnection $dbConn = null) {
		$this->dbConn = $dbConn;
	}

	/**
	 * FIXME: This function should not be required!
	 */
	private function fixDI(): void {
		if ($this->dbConn === null) {
			$this->dbConn = \OC::$server->getDatabaseConnection();
		}
	}

	public function inGroup($uid, $gid): bool {
		if (!in_array($gid, $this->systemGroups)) {
			return false;
		}
		if (!$this->canExposeThatIsInGroup()) {
			return false;
		}
		$this->fixDI();

		// check
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('uid')
			->from('group_user')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$result = $cursor->fetch();
		$cursor->closeCursor();

		return $result ? true : false;
	}

	/**
	 * Check by backtrace if this group or a member of this group  can be
	 * exposed
	 */
	private function canExposeThatIsInGroup(): bool {
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$allowedFunctions = [
			'removeUser',
			'addUser',
		];
		foreach ($backtrace as $step) {
			if (in_array($step['function'], $allowedFunctions)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup(string $uid, string $gid): bool {
		$this->fixDI();

		// No duplicate entries!
		if (!$this->inGroup($uid, $gid)) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->insert('group_user')
				->setValue('uid', $qb->createNamedParameter($uid))
				->setValue('gid', $qb->createNamedParameter($gid))
				->executeStatement();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup(string $uid, string $gid): bool {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_user')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		return true;
	}

	/**
	 * get the number of all users matching the search string in a group
	 * @param string $gid
	 * @param string $search
	 * @return int
	 */
	public function countUsersInGroup(string $gid, string $search = ''): int {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select($query->func()->count('*', 'num_users'))
			->from('group_user')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));

		if ($search !== '') {
			$query->andWhere($query->expr()->like('uid', $query->createNamedParameter(
				'%' . $this->dbConn->escapeLikeParameter($search) . '%'
			)));
		}

		$result = $query->execute();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	public function getDisplayName(string $gid): string {
		return 'Waiting approval';
	}

	public function getUserGroups($uid) {
		//guests has empty or null $uid
		if ($uid === null || $uid === '') {
			return [];
		}

		$this->fixDI();

		// No magic!
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('gu.gid', 'g.displayname')
			->from('group_user', 'gu')
			->leftJoin('gu', 'groups', 'g', $qb->expr()->eq('gu.gid', 'g.gid'))
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$groups = [];
		while ($row = $cursor->fetch()) {
			$groups[] = $row['gid'];
		}
		$cursor->closeCursor();

		return $groups;
	}

	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return $this->systemGroups;
	}

	public function groupExists($gid) {
		if (in_array($gid, $this->systemGroups)) {
			return true;
		}
		return false;
	}

	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if (!in_array($gid, $this->systemGroups)) {
			return [];
		}
		return array_values(array_map(fn ($user) => $user->getUid(), $this->searchInGroup($gid, $search, $limit, $offset)));
	}

	private function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select('g.uid', 'u.displayname');

		$query->from('group_user', 'g')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->orderBy('g.uid', 'ASC');

		$query->leftJoin('g', 'users', 'u', $query->expr()->eq('g.uid', 'u.uid'));

		if ($search !== '') {
			$query->leftJoin('u', 'preferences', 'p', $query->expr()->andX(
				$query->expr()->eq('p.userid', 'u.uid'),
				$query->expr()->eq('p.appid', $query->expr()->literal('settings')),
				$query->expr()->eq('p.configkey', $query->expr()->literal('email'))
			))
				// sqlite doesn't like re-using a single named parameter here
				->andWhere(
					$query->expr()->orX(
						$query->expr()->ilike('g.uid', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')),
						$query->expr()->ilike('u.displayname', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')),
						$query->expr()->ilike('p.configvalue', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%'))
					)
				)
				->orderBy('u.uid_lower', 'ASC');
		}

		if ($limit !== -1) {
			$query->setMaxResults($limit);
		}
		if ($offset !== 0) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();

		$users = [];
		$userManager = \OCP\Server::get(IUserManager::class);
		while ($row = $result->fetch()) {
			$users[$row['uid']] = new LazyUser($row['uid'], $userManager, $row['displayname'] ?? null);
		}
		$result->closeCursor();

		return $users;
	}

	public function hideGroup(string $groupId): bool {
		return true;
	}
}
