<?php

namespace App\Services;

/**
 * WHAT THE BACKUP COLUMN ACTUALLY LOOKS AT.
 *
 * backup.sh writes /home/khansland/backups/<database>_<date>.sql.gz, mode 600,
 * owned by khansland, in a directory that is world-listable. So this can read
 * the NAME, the MTIME and the SIZE of a dump without being able to open it -
 * which is the right amount of access for a column that only has to say "yes,
 * recently, and this big".
 *
 * IT MUST NOT BE CALLED FROM A WEB REQUEST. The console's php-fpm pool sets
 * open_basedir to its own deployment, so a request cannot stat that directory
 * at all. That is deliberate, and it is why this runs inside saas:stats.
 *
 * THREE STATES, NEVER A BLANK
 *   ok          a dump exists; its age and size are the evidence
 *   none_found  the directory was readable and there is no dump for this
 *               database. This is the LOUD one - it is the state that should
 *               have shouted on 14 and 15 August, when the backup ran for two
 *               nights and never reached the upload.
 *   cannot_look the directory could not be listed from here. Honest ignorance,
 *               and not to be dressed up as either of the other two.
 */
class BackupEvidence
{
    /** Where backup.sh puts them. Nothing else is searched, so nothing else can lie. */
    public const DIRS = ['/home/khansland/backups'];

    /** Older than this and a backup is not a backup. */
    public const STALE_HOURS = 48;

    /**
     * @return array{state:string,file:?string,at:?string,bytes:?int,count:?int,note:?string}
     */
    public static function for(string $database): array
    {
        $blank = ['state' => 'cannot_look', 'file' => null, 'at' => null,
                  'bytes' => null, 'count' => null, 'note' => null];

        $looked = false;
        $files = [];
        foreach (self::DIRS as $dir) {
            if (! is_dir($dir) || ! is_readable($dir)) {
                continue;
            }
            $looked = true;
            // The trailing _ matters: without it tenant_aquademo would also
            // match a database called tenant_aquademo_old, and the column would
            // report somebody else's backup as this tenant's.
            foreach ((array) (@glob($dir . '/' . $database . '_*.sql.gz') ?: []) as $f) {
                $mtime = @filemtime($f);
                if ($mtime !== false) {
                    $files[$f] = $mtime;
                }
            }
        }

        if (! $looked) {
            return $blank + ['note' => 'no backup directory is readable from here'];
        }

        if (! $files) {
            return ['state' => 'none_found', 'file' => null, 'at' => null, 'bytes' => null,
                    'count' => 0, 'note' => 'the backup directory was readable and holds no dump for '
                    . $database];
        }

        arsort($files);
        $newest = array_key_first($files);

        return [
            'state' => 'ok',
            'file' => basename($newest),
            'at' => date('Y-m-d H:i:s', $files[$newest]),
            'bytes' => (int) (@filesize($newest) ?: 0),
            'count' => count($files),
            'note' => null,
        ];
    }
}
