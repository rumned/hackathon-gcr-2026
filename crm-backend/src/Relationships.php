<?php

declare(strict_types=1);

namespace App;

/**
 * Rows in relationship tables such as accounts_contacts.
 * "lhs" is the account side, "rhs" the contact / opportunity side.
 * A contact or opportunity is linked to at most one account.
 */
final class Relationships
{
    /** rhs id => lhs id (e.g. contact id => account id), skipping deleted accounts. */
    public static function lhsFor(string $name, array $rhsIds): array
    {
        if (!$rhsIds) {
            return [];
        }
        $rel = Modules::relationship($name);
        $lhsTable = Modules::get($rel['lhs_module'])['table'];
        $rows = Db::all(
            "SELECT r.`{$rel['rhs_key']}` AS rhs, r.`{$rel['lhs_key']}` AS lhs FROM `{$rel['table']}` r"
            . " JOIN `$lhsTable` l ON l.id = r.`{$rel['lhs_key']}` AND l.deleted = 0"
            . " WHERE r.`{$rel['rhs_key']}` IN (" . Db::marks($rhsIds) . ')',
            $rhsIds,
        );

        return array_column($rows, 'lhs', 'rhs');
    }

    /** Sets (or clears, with null) the account of a contact / opportunity. */
    public static function setLhs(string $name, string $rhsId, ?string $lhsId): void
    {
        $rel = Modules::relationship($name);
        Db::run("DELETE FROM `{$rel['table']}` WHERE `{$rel['rhs_key']}` = ?", [$rhsId]);
        if ($lhsId !== null) {
            Db::insert($rel['table'], [
                'id' => Util::uuid(),
                $rel['lhs_key'] => $lhsId,
                $rel['rhs_key'] => $rhsId,
                'date_modified' => Util::now(),
            ]);
        }
    }

    /** Returns false when the two records were not linked. */
    public static function unlink(string $name, string $lhsId, string $rhsId): bool
    {
        $rel = Modules::relationship($name);

        return Db::run(
            "DELETE FROM `{$rel['table']}` WHERE `{$rel['lhs_key']}` = ? AND `{$rel['rhs_key']}` = ?",
            [$lhsId, $rhsId],
        ) > 0;
    }

    /** Merge: moves links from the merged-away records to the kept record. */
    public static function merge(string $moduleKey, array $duplicateIds, string $primaryId): void
    {
        $marks = Db::marks($duplicateIds);
        foreach (Modules::relationships() as $rel) {
            if ($rel['lhs_module'] === $moduleKey) {
                // e.g. merging accounts: their contacts now belong to the kept account
                Db::run(
                    "UPDATE `{$rel['table']}` SET `{$rel['lhs_key']}` = ? WHERE `{$rel['lhs_key']}` IN ($marks)",
                    [$primaryId, ...$duplicateIds],
                );
            }
            if ($rel['rhs_module'] === $moduleKey) {
                // e.g. merging contacts: keep the kept contact's account, or take one from a duplicate
                $hasLink = Db::value("SELECT 1 FROM `{$rel['table']}` WHERE `{$rel['rhs_key']}` = ?", [$primaryId]) !== null;
                if (!$hasLink) {
                    Db::run(
                        "UPDATE `{$rel['table']}` SET `{$rel['rhs_key']}` = ? WHERE `{$rel['rhs_key']}` IN ($marks) LIMIT 1",
                        [$primaryId, ...$duplicateIds],
                    );
                }
                Db::run("DELETE FROM `{$rel['table']}` WHERE `{$rel['rhs_key']}` IN ($marks)", $duplicateIds);
            }
        }
    }
}
