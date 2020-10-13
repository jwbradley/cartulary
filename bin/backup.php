<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//TODO: add cart directory to backup as well so we can quickly restore both db and app from backup

//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    //Reset logs
    $fp = fopen("$confroot/$log/$acclog", "w");
    fclose($fp);
    $fp = fopen("$confroot/$log/$errlog", "w");
    fclose($fp);
    $fp = fopen("$confroot/$log/$dbglog", "w");
    fclose($fp);

    //Is s3 enabled and a backup bucket specified?
    if (!sys_s3_is_enabled()) {
        loggit(3, "System level S3 not enabled in conf file.");
        cronHelper::unlock();
        exit(0);
    }
    if (empty($s3_sys_backup)) {
        loggit(3, "S3 backup bucket not specified in conf file.");
        cronHelper::unlock();
        exit(0);
    }

    //We're good, so do the backup
    loggit(3, "Backing up the database...");
    $tstart = time();

    //Create a filename to use for this backup
    $filename = $dbname . "_backup." . date('Y-m-d.His') . ".sql.gz";
    $dumpfile = sys_get_temp_dir() . "/" . $filename;

    //Was a backup folder specified in the conf file?
    if(!empty($cg_backup_temp_folder)) {
        $dumpfile = rtrim($cg_backup_temp_folder, '/ ') . "/" . $filename;
    }

    //Run mysqldump command
    if ($cg_backup_encrypt == 1) {
        $cmdtorun = "mysqldump --single-transaction --quick -h$dbhost -u$dbuser -p$dbpass $dbname 
        --ignore-table=$dbname.$table_nfitem 
        --ignore-table=$dbname.$table_nfitem_map_catalog 
        --ignore-table=$dbname.$table_nfenclosures 
        --ignore-table=$dbname.$table_nfitem_map 
        --ignore-table=$dbname.$table_nfitemprop 
        | cstream -t 1000000 
        | gzip -c 
        | openssl enc -aes-256-cbc -salt -pass pass:$cg_backup_encrypt_password -out $dumpfile";
    } else {
        $cmdtorun = "mysqldump --single-transaction --quick -h$dbhost -u$dbuser -p$dbpass $dbname 
        --ignore-table=$dbname.$table_nfitem 
        --ignore-table=$dbname.$table_nfitem_map_catalog 
        --ignore-table=$dbname.$table_nfenclosures 
        --ignore-table=$dbname.$table_nfitem_map 
        --ignore-table=$dbname.$table_nfitemprop 
        | cstream -t 1000000 
        | gzip -c > $dumpfile";
    }
    loggit(3, "BACKUP: Running command: [$cmdtorun].");
    $output = `$cmdtorun`;
    loggit(3, "BACKUP: Result: [" . print_r($output, TRUE) . "]");

    //Get the file size
    $filesize = filesize($dumpfile);

    //If we can get some sane S3 credentials then let's go
    $s3info = get_sys_s3_info();
    if ($s3info != FALSE) {
        //Put the file in S3
        $s3res = putFileInS3($dumpfile, $filename, $s3info['backup'], $s3info['key'], $s3info['secret'], "text/plain", TRUE);
        if (!$s3res) {
            loggit(3, "Could not write database backup: [$filename | " . format_bytes($filesize) . "] to S3 in bucket: [" . $s3info['backup'] . "].");
        } else {
            loggit(3, "Wrote database backup: [$filename | " . format_bytes($filesize) . "] to S3 in bucket: [" . $s3info['backup'] . "].");
        }
    }

    //Calculate how long it took to backup the database
    $took = time() - $tstart;
    echo "It took: [$took] seconds to backup the database.";
    loggit(3, "It took: [$took] seconds to backup the database.");

    //Add an administrative log entry for this event
    add_admin_log_item("Wrote database backup: [$filename | " . format_bytes($filesize) . "] to S3 in bucket: [" . $s3info['backup'] . "].  The operation took: [$took] seconds.", "Backup Complete");

    //Clean up the temporary dump file
    if (!unlink($dumpfile)) {
        loggit(3, "Could not remove temporary database dump file. Check file/user permissions.");
    }

    //Release the lock
    cronHelper::unlock();
}
exit(0);