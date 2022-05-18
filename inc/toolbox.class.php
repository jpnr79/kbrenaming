<?php

/**
 *
 * toolbox.class.php
 *
 *
 *
 * @version GIT: $
 * @author  Sébastien Batteur <sebastien.batteur@brussels.msf.org>
 */
class PluginKbrenamingToolbox
{
    const BITS = 1;
    const OCTETS = 2;
    private const SHMOP_KEY = 0x4c61737452657175;
    const SHMOP_SIZE = 15;

    public static function getLastRequest(): float{
        $shm_id = shmop_open(self::SHMOP_KEY, "c", 0666, (int)16);
        $shm_size = shmop_size($shm_id);
        return floatval(shmop_read($shm_id, 0, $shm_size));
    }

    public static function setLastRequest(float $now = 0.0): void{
        $now = $now??microtime(true);
        $shm_id = shmop_open(self::SHMOP_KEY, "c", 0666, self::SHMOP_SIZE);
        shmop_write($shm_id, $now, 0);
    }

    public static function change_softwareversion(int $old_id, int $new_id): bool
    {
        global $DB;
       if ($old_id === $new_id){
            return true;
        }
        $result = $DB->query(
            "
            UPDATE `" . Item_SoftwareVersion::getTable() . "`
            SET  `" . Item_SoftwareVersion::getTable() . "`.`softwareversions_id` = '" . $new_id . "'
            WHERE `" . Item_SoftwareVersion::getTable() . "`.`softwareversions_id` = '" . $old_id . "' ;
            "
        );
        return $result;
    }

    public static function str_union(string $string1, string $string2, int $master_string = 0, int $minimum_same = 0): string{
        if (empty($string1)) {
            return $string2;
        }
        if (empty($string2)) {
            return $string1;
        }
        $return = '';
        for($i=0; $i<min(strlen($string1), strlen($string2)); $i++){
            if (strcasecmp($string1[$i], $string2[$i]) == 0){
                $return .= $string1[$i];
            }else{
                break;
            }
        }
        if (strlen($return)<$minimum_same){
            $return ='';
        }
        $string = [$string1,$string2];
        return $return?:$string[$master_string];
    }

}