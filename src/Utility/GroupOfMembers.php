<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\Utility;

use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class GroupOfMembers
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getAllExistingGroups(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, name FROM tl_member_group ORDER BY name',
        );

        $groups = [];

        foreach ($rows as $row) {
            $groups[(int) $row['id']] = (string) $row['name'];
        }

        return $groups;
    }

    /**
     * @param list<int|string> $selectedGroupIds
     *
     * @return list<string>
     */
    public function getGroupNames(array $selectedGroupIds): array
    {
        $groupIds = array_values(array_unique(array_filter(array_map('intval', $selectedGroupIds))));

        if (!$groupIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, name FROM tl_member_group WHERE id IN (?)',
            [$groupIds],
            [ArrayParameterType::INTEGER],
        );

        $groupNamesById = [];

        foreach ($rows as $row) {
            $groupNamesById[(int) $row['id']] = (string) $row['name'];
        }

        $groupNames = [];

        foreach ($groupIds as $groupId) {
            if (isset($groupNamesById[$groupId])) {
                $groupNames[] = $groupNamesById[$groupId];
            }
        }

        return $groupNames;
    }

    /**
     * @param list<int|string> $groupIds
     *
     * @return list<array<string, mixed>>
     */
    public function getMembersOfGroupIds(array $groupIds): array
    {
        $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));

        if (!$groupIds) {
            return [];
        }

        $members = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_member WHERE groups IS NOT NULL AND groups != ?',
            [''],
        );

        $groupMap = array_flip($groupIds);
        $result = [];

        foreach ($members as $member) {
            $memberGroups = array_map('intval', StringUtil::deserialize($member['groups'] ?? null, true));

            foreach ($memberGroups as $groupId) {
                if (!isset($groupMap[$groupId])) {
                    continue;
                }

                $member['group_id'] = $groupId;
                $result[] = $member;
            }
        }

        return $result;
    }

}
