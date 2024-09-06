<?php

CModule::IncludeModule("tasks");

$extractNumber = function ($string, $level) {
    $parts = explode(' ', $string);
    $numOfDashes = substr_count($parts[0], '-');

    if ($level == $numOfDashes) {
        $numberPart = $parts[0];
        $count = 0;

        for ($i = 0; $i < strlen($numberPart); $i++) {
            if ($numberPart[$i] == '-') {
                $count++;
            }

            if ($count == $level) {
                $start = $i + 1;
                break;
            }
        }

        $end = strpos($numberPart, ' ');

        if ($end !== false) {
            $number = substr($numberPart, $start, $end - $start);
        } else {
            $number = substr($numberPart, $start);
        }

        return $number !== '' ? $number : NULL;
    } else {
        return NULL;
    }
};

$checkDuplicatesAndGaps = function ($array) {

    if (is_countable($array)) {
        $n = count($array);
    } else {
        return false;
    }

    $expectedSum = ($n * ($n + 1)) / 2;

    $currentSum = 0;
    $prevNumber = 0;

    foreach ($array as $number) {
        $currentSum += (int)  $number;
        if ($number == $prevNumber) {
            return false;
        } elseif ($number > $prevNumber + 1) {
            return false;
        }
        $prevNumber = $number;
    }

    if ($currentSum != $expectedSum) {
        return false;
    }

    return true;
};


$extractDealNumber = function ($string) {
    $parts = explode('-', $string);
    $number = trim($parts[0]);
    return $number !== '' ? $number : null;
};

$checkFormat = function ($string) {
    if (empty($string)) {
        return false;
    }

    $parts = explode("-", explode(" ", $string)[0]);

    foreach ($parts as $part) {
        if (!is_numeric($part)) {
            return false;
        }
    }

    return true;
};

$setCrm = function ($connection, $idTask, $crmNew) {

    $connection->query("DELETE FROM  b_utm_tasks_task WHERE FIELD_ID = 6 AND VALUE_ID = {$idTask}");
    $connection->query("UPDATE b_uts_tasks_task SET UF_CRM_TASK = '{$crmNew}' WHERE VALUE_ID = {$idTask}");

    $crmArray = unserialize($crmNew);

    $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID = " . explode("_", $crmArray[0])[1] . " WHERE DST_ENTITY_ID IN
    (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_128
    ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_128.ID
    WHERE UF_TASK_NUM = {$idTask} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 128) AS TMP) AND DST_ENTITY_TYPE_ID = 128");

    $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID =  " . explode("_", $crmArray[0])[1] . "  WHERE DST_ENTITY_ID IN
    (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_167
    ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_167.ID
    WHERE UF_TASK_ID  = {$idTask} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 167) AS TMP) AND DST_ENTITY_TYPE_ID = 167");

    foreach ($crmArray as $cm) {
        $connection->query("INSERT INTO b_utm_tasks_task  (VALUE_ID, FIELD_ID, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE) VALUES ({$idTask}, 6, '{$cm}', NULL, NULL, NULL)");
    }
};

$traverse = function ($connection, $idTask, $crmNew) use (&$traverse, &$setCrm) {

    $recordset12 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE PARENT_ID = {$idTask}");

    while ($record12 = $recordset12->fetch()) {
        $crmArray = unserialize($crmNew);

        $pid = $record12["ID"];

        $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID = " . explode("_", $crmArray[0])[1] . " WHERE DST_ENTITY_ID IN
        (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_128
        ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_128.ID
        WHERE UF_TASK_NUM = {$pid} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 128) AS TMP) AND DST_ENTITY_TYPE_ID = 128");

        $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID =  " . explode("_", $crmArray[0])[1] . "  WHERE DST_ENTITY_ID IN
        (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_167
        ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_167.ID
        WHERE UF_TASK_ID  = {$pid} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 167) AS TMP) AND DST_ENTITY_TYPE_ID = 167");

        $arFields = array(
            "UF_CRM_TASK" => $crmArray,
        );

        $obTask = new CTasks;
        $success = $obTask->Update($pid, $arFields);

        //       $setCrm($connection, $pid, $crmNew);

        //      $traverse($connection, $pid, $crmNew);
    }
};

$id = "{{ID_TASK}}";

$idTask = $id;

$connection = Bitrix\Main\Application::getConnection();

$sql3 = "SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID  WHERE ID = {$id}";

$recordset3 = $connection->query($sql3);

if ($record3 = $recordset3->fetch()) {

    $zombie = $record3["ZOMBIE"];

    $detach = $record3["UF_TASK_DISK_DETACH"];

    $isFolder = $record3["UF_IS_FOLDER"];

    $crm = $record3["UF_CRM_TASK"];

    $crmAr = unserialize($crm);

    if (is_countable($crmAr) && count($crmAr) > 1) {
        $crmAr = array($crmAr[0]);

        $crm = serialize($crmAr);

        $setCrm($connection, $id, $crm);
    }

    if (is_countable($crmAr) && count($crmAr) == 1) {

        $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID = " . explode("_", $crmAr[0])[1] . " WHERE DST_ENTITY_ID IN
            (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_128
            ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_128.ID
            WHERE UF_TASK_NUM = {$id} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 128) AS TMP) AND DST_ENTITY_TYPE_ID = 128");

        $connection->query("UPDATE b_crm_entity_relation SET SRC_ENTITY_ID =  " . explode("_", $crmAr[0])[1] . "  WHERE DST_ENTITY_ID IN
            (SELECT DST_ENTITY_ID FROM (SELECT DST_ENTITY_ID FROM  b_crm_entity_relation INNER JOIN b_crm_dynamic_items_167
            ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_167.ID
            WHERE UF_TASK_ID  = {$id} AND SRC_ENTITY_TYPE_ID = 2 AND DST_ENTITY_TYPE_ID = 167) AS TMP) AND DST_ENTITY_TYPE_ID = 167");
    }


    if (isset($record3["PARENT_ID"])) {

        $parent = $record3["PARENT_ID"];


        $recordset11 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE ID = {$parent}");

        if ($record11 = $recordset11->fetch()) {


            $crmParent = $record11["UF_CRM_TASK"];

            if ($crm != $crmParent) {

                $setCrm($connection, $id, $crmParent);

                $crm = $crmParent;
            }
        }
    }

    $crmTo = $crm;

    //  $traverse($connection, $id, $crm);

    $title = $record3["TITLE"];

    $titleTask = $record3["TITLE"];

    $crm = unserialize($crm)[0];

    $px = explode("_", $crm)[0];

    if (!$crm || ($px != "D" && $px != "L")) {

        if (isset($record3["UF_FOLDER_ID"])) {
            $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

            if ($folder) {
                $idFolder = $folder->getChild(
                    array(
                        '=NAME' => '!ИД_СДЕЛКИ',
                        'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER
                    )
                );

                if ($idFolder) {
                    $idFolder->markDeleted(1);
                }

                $fd = $record3["UF_FOLDER_ID"];

                $sql99 = "SELECT * FROM b_disk_object WHERE REAL_OBJECT_ID = {$fd}";

                $recordset99 = $connection->query($sql99);

                while ($record99 = $recordset99->fetch()) {
                    if ($record99["ID"] != $fd && $record99["STORAGE_ID"] == 488) {
                        $sourceObject = \Bitrix\Disk\BaseObject::loadById($record99["ID"]);

                        if ($sourceObject) {
                            $sourceObject->markDeleted(1);
                        }
                    }
                }
            }
        }
    }

    $titleTaskFolder = $titleTask;

    $forbidden = '\/:*?"<>|+%!@';
    $titleTaskFolder  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder);

    if ($titleTaskFolder[strlen($titleTaskFolder) - 1] == ".") {
        $titleTaskFolder[strlen($titleTaskFolder) - 1] = " ";
    }

    ////////////////////////////////////////////////

    if ($px == "L") {

        $crm = explode("_", $crm)[1];

        $sql4 = "SELECT * FROM  b_crm_lead INNER JOIN  b_uts_crm_lead ON b_crm_lead.ID =  b_uts_crm_lead.VALUE_ID 
                        WHERE ID = {$crm}";

        $recordset4 = $connection->query($sql4);

        if ($record4 = $recordset4->fetch()) {
            if (isset($record4["UF_CATALOG_LEAD"])) {

                if (!isset($record3["UF_FOLDER_ID"])) {
                    $commonTaskFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG_LEAD"]);

                    if ($commonTaskFolder) {
                        $taskFolder = $commonTaskFolder->addSubFolder(array(
                            'NAME' => $titleTaskFolder,
                            'CREATED_BY' => 1
                        ));

                        if ($taskFolder) {

                            $fld = $taskFolder;

                            $taskFolderId = $taskFolder->getId();

                            if (isset($taskFolderId)) {


                                /*   $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId} WHERE VALUE_ID = {$id}"); 

                                    $leadFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG_LEAD"]);


                                    if($leadFolder) {


                                        $tskInFolder = $leadFolder->addSubFolder(    array( 
                                            'NAME' => $titleTaskFolder,  
                                            'CREATED_BY' => 1 
                                        )); 

                                        if($tskInFolder) {
                                            $tid = $tskInFolder->getId();
                                        }
                                    }*/


                                /*  if($tid) {

                                        $sql66 = "SELECT * FROM b_disk_object WHERE ID = {$tid}";

                                        $recordset66 = $connection->query($sql66);

                                        if ($record66 = $recordset66->fetch()) {
                                            if($record66["REAL_OBJECT_ID"] != $taskFolderId ) {
                                                $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$taskFolderId} WHERE ID = {$tid}");

                                                $arMessageFields = array(
                                                    "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                                                    "FROM_USER_ID" => 5,
                                                    "TO_USER_ID" =>  5,
                                                    "NOTIFY_MESSAGE" =>  "_".$taskFolderId." ".$tid, 
                                                    "NOTIFY_MESSAGE_OUT" => "_".$taskFolderId." ".$tid, 
                                                    "NOTIFY_MODULE" => "bizproc",
                                                    "NOTIFY_EVENT" => "activity"
                                                );
                                                
                                                //CIMNotify::Add($arMessageFields);
                                            }
                                        }
                                    } */
                            }
                        }
                    }
                } else {
                    $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

                    if ($folder)
                        $folder->rename($titleTaskFolder);

                    //  $href = "";

                    //  $sql5 = "UPDATE b_uts_tasks_task SET UF_CATALOG_HREF = '{$href}' WHERE VALUE_ID = {$id}";

                    //     $connection->query($sql5);

                    $fld = $folder;
                }

                if ($fld) {
                    $users = array();

                    /*  $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
                        
                        while ($record12 = $recordset12->fetch()) {
                            $users[]=$record12["USER_ID"];
                        }*/

                    $recordset12 = $connection->query("SELECT * FROM b_uts_tasks_task WHERE VALUE_ID = {$id}");

                    while ($record12 = $recordset12->fetch()) {
                        $users = unserialize($record12["UF_TASK_FOLDER_MEMBERS"]);

                        if (!$users) {
                            $users = array();
                        }
                    }

                    $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();

                    $accessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

                    $newRights = array();

                    $fd = $fld->getId();

                    $errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();

                    $sql = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 1 AND ENTITY_ID = {$crm}";

                    $recordset = $connection->query($sql);

                    while ($record = $recordset->fetch()) {
                        $users[] = $record["USER_ID"];
                    }

                    $sql = "SELECT * FROM b_crm_lead WHERE ID = {$crm}";

                    $recordset = $connection->query($sql);

                    if ($record = $recordset->fetch()) {
                        $users[] = $record["ASSIGNED_BY_ID"];
                        $users[] = $record["CREATED_BY"];
                    }


                    foreach ($users as $user) {
                        if ($user != "") {
                            $newRights[] =    array(
                                'ACCESS_CODE' =>  "U" . $user,
                                'TASK_ID' =>  $accessTaskId,
                            );
                        }
                    }

                    /* Bitrix\Disk\Sharing::connectToUserStorage(
                                    $user, array(
                                        'SELF_CONNECT' => true,
                                        'CREATED_BY' => $user,
                                        'REAL_OBJECT' => $fld,
                                    ), $errorCollection
                                ); */
                }


                $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

                $recordset5 = $connection->query($sql5);

                while ($record5 = $recordset5->fetch()) {
                    $est  = false;

                    foreach ($users as $user) {
                        if ($user == $record5["CREATED_BY"]) {
                            $est = true;
                            break;
                        }
                    }

                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                    if (!$est) {

                        // $sourceObject->markDeleted($record5["CREATED_BY"]);
                    } else {
                        //  $sourceObject->rename($titleTaskFolder);
                    }
                }

                //$rightsManager->delete($fld);
                //  $rightsManager->set($fld, $newRights);
            }
        }
    }

    ////////////////////////////////////////////////

    ////////////////////////////////////////////////
    if ($px == "D") {


        $crm = explode("_", $crm)[1];

        $idDeal = $crm;

        $sql4 = "SELECT * FROM  b_crm_deal INNER JOIN  b_uts_crm_deal ON b_crm_deal.ID =  b_uts_crm_deal.VALUE_ID   WHERE ID = {$crm}";

        $recordset4 = $connection->query($sql4);

        if ($record4 = $recordset4->fetch()) {


            if ($crm > 1/*$crm >= 49602 || $record4["UF_OLD_DEAL_CATALOG"] == 1*/) {

                if ($crm >= 52102) {
                    $numberDeal = sprintf("%04d", $crm - 52101);
                } else if (isset($record4["UF_OLD_ID"])) {
                    $numberDeal = $record4["UF_OLD_ID"];
                } else if ($crm > 42102) {
                    $numberDeal = $crm - 42102;
                } else {
                    $numberDeal = 42102;
                }

                //  $i=0;
                //   $j=0;

                $deal = $numberDeal;

                $sql9 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE ID = {$idTask}";

                $recordset9 = $connection->query($sql9);

                $maybeNotNeed = false;

                if ($record9 = $recordset9->fetch()) {

                    if ($record9["UF_DEPTH_LEVEL"]) {

                        $depth = $record9["UF_DEPTH_LEVEL"];
                        $parent = $record9["PARENT_ID"];

                        //    echo $parent."\n";

                        $countTasks = 0;
                        $parentStr = "PARENT_ID = {$parent}";

                        if (!$parent) {
                            $parentStr = "(PARENT_ID = 0 OR PARENT_ID IS NULL)";
                        }

                        $sql98 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID 
WHERE UF_DEPTH_LEVEL = {$depth} AND UF_CRM_TASK LIKE '%\\\\\"D_{$crm}\\\\\"%' AND {$parentStr} AND ZOMBIE = 'N'  ORDER BY ID ASC";

                        $recordset98 = $connection->query($sql98);

                        $numbersOnLevel = array();

                        $currentNumber = NULL;

                        while ($record98 = $recordset98->fetch()) {

                            $number = $extractNumber($record98["TITLE"], $depth);
                            $dealNumber = $extractDealNumber($record98["TITLE"]);

                            //  echo $record98["ID"]." ".$record98["TITLE"]." ".$number."\n";

                            if ($record98["ID"] == $idTask && $number != NULL && $dealNumber == $deal) {
                                $maybeNotNeed = true;
                            }

                            $numbersOnLevel[] = $number;

                            $countTasks++;
                        }

                        sort($numbersOnLevel);

                        //   echo print_r($numbersOnLevel);

                        if (!$checkDuplicatesAndGaps($numbersOnLevel)) {

                            $notCorrect = false;
                            $parentTitle = "";

                            if ($parent) {
                                $sql8 = "SELECT * FROM b_tasks WHERE ID = {$parent}";

                                $recordset8 = $connection->query($sql8);

                                if ($record8 = $recordset8->fetch()) {

                                    $title = $record8["TITLE"];

                                    if ($extractDealNumber($record8["TITLE"]) == $deal) {

                                        $parentTitle = explode(" ", $record8["TITLE"])[0];
                                    } else {
                                        $notCorrect = true;
                                    }
                                }
                            }

                            if (!$notCorrect) {

                                $parentStr = "PARENT_ID = {$parent}";

                                if (!$parent) {
                                    $parentStr = "(PARENT_ID = 0 OR PARENT_ID IS NULL)";
                                }

                                $sql98 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID 
WHERE UF_DEPTH_LEVEL = {$depth} AND UF_CRM_TASK LIKE '%\\\\\"D_{$crm}\\\\\"%' AND {$parentStr} AND ZOMBIE = 'N'  ORDER BY ID ASC";

                                $recordset98 = $connection->query($sql98);

                                $i = 1;

                                while ($record98 = $recordset98->fetch()) {

                                    $title = $record98["TITLE"];

                                    if ($checkFormat($title)) {
                                        //  $title = explode(" ", $title)[1];
                                        $title =  implode(" ", array_slice(explode(" ", $title), 1));
                                    }

                                    if ($parentTitle != "") {
                                        $title = $parentTitle . "-" . $i . " " . $title;
                                    } else {
                                        $title = $numberDeal . "-" . $i . " " . $title;
                                    }

                                    $connection->query("UPDATE b_tasks SET TITLE = '{$title}' WHERE ID =" . $record98["ID"]);

                                    if ($record98["ID"] == $idTask) {
                                        $titleTask = $title;
                                    }

                                    $i++;

                                    //   echo $title;
                                }
                            }
                        } else {

                            if (!$maybeNotNeed) {

                                if ($parent) {
                                    $sql8 = "SELECT * FROM b_tasks WHERE ID = {$parent}";

                                    $recordset8 = $connection->query($sql8);

                                    if ($record8 = $recordset8->fetch()) {

                                        $title = $record8["TITLE"];

                                        if ($extractDealNumber($record8["TITLE"]) == $deal) {

                                            $parentTitle = explode(" ", $record8["TITLE"])[0];
                                        } else {
                                            $notCorrect = true;
                                        }
                                    }
                                }

                                if (!$notCorrect) {

                                    $sql8 = "SELECT * FROM b_tasks WHERE ID = {$idTask}";

                                    $recordset8 = $connection->query($sql8);

                                    if ($record8 = $recordset8->fetch()) {

                                        $title = $record8["TITLE"];

                                        if ($parentTitle != "") {
                                            $title = $parentTitle . "-" . $countTasks . " " . $title;
                                        } else {
                                            $title = $numberDeal . "-" . $countTasks . " " . $title;
                                        }

                                        $connection->query("UPDATE b_tasks SET TITLE = '{$title}' WHERE ID =" . $idTask);

                                        $titleTask = $title;
                                    }
                                }
                            }
                        }
                    }
                }
                $traverse($connection, $id, $crmTo);

                $crm = "D_" . $crm;

                /*   $mainTask = $id;

              if(isset($record3["PARENT_ID"]) && $record3["PARENT_ID"] !=0) {
                    $mainTask = $record3["PARENT_ID"];

                    $recordset12 = $connection->query("SELECT * FROM  b_tasks WHERE PARENT_ID = {$mainTask} AND ZOMBIE ='N' ORDER BY ID ASC"); 

                    while ($record12 = $recordset12->fetch()) {
                        $j++;
    
                        if($record12["ID"] == $id) {
                            break;
                        }
                    }
                }

                $recordset11 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE UF_CRM_TASK LIKE '%\"{$crm}\"%' AND ZOMBIE ='N' AND (PARENT_ID IS NULL OR PARENT_ID = 0)   ORDER BY ID ASC"); 

                while ($record11 = $recordset11->fetch()) {
                    $i++;

                    if($record11["ID"] == $mainTask) {
                        break;
                    }
                }

                $ts = explode(" ", $titleTask)[0];

                if($ts) {
                    $tsAr = explode("-", $ts);

                    if(is_countable($tsAr) && count($tsAr) == 2) {

                        $ts01 = explode("-", $ts)[0];
                        $ts02 = explode("-", $ts)[1];

                        if(is_numeric($ts01) && is_numeric($ts02)) {

                            $titleTasks = explode(" ", $titleTask);

                            $titleTask="";

                            for($h=1; $h<count($titleTasks); $h++) {
                                $titleTask.=$titleTasks[$h];

                                if($h!=count($titleTasks)-1) {
                                    $titleTask.=" ";
                                }
                            }

                        }
                    }

                    if(is_countable($tsAr) && count($tsAr) == 3) {
                        $ts01 = explode("-", $ts)[0];
                        $ts02 = explode("-", $ts)[1];
                        $ts03 = explode("-", $ts)[2];

                        if(is_numeric($ts01) && is_numeric($ts02) && is_numeric($ts03)) {
                            $titleTasks = explode(" ", $titleTask);

                            $titleTask="";

                            for($h=1; $h<count($titleTasks); $h++) {
                                $titleTask.=$titleTasks[$h];

                                if($h!=count($titleTasks)-1) {
                                    $titleTask.=" ";
                                }
                            }
                        }
                    }

                } 

                if(isset($record3["PARENT_ID"]) && $record3["PARENT_ID"]!=0) {
                    $titleTask = $numberDeal."-".$i."-".$j." ".$titleTask;
                } else {
                    $titleTask = $numberDeal."-".$i." ".$titleTask;
                } */

                /* $arMessageFields = array(
                    "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                    "FROM_USER_ID" => 5,
                    "TO_USER_ID" =>  5,
                    "NOTIFY_MESSAGE" =>  "_".$record3["PARENT_ID"], 
                    "NOTIFY_MESSAGE_OUT" => "_".$record3["PARENT_ID"], 
                    "NOTIFY_MODULE" => "bizproc",
                    "NOTIFY_EVENT" => "activity"
                );
                
                CIMNotify::Add($arMessageFields);*/

                $titleTask = mb_substr($titleTask, 0, 50);

                $connection->query("UPDATE b_tasks SET TITLE = '{$titleTask}' WHERE ID={$id}");

                //  $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE UF_CRM_TASK LIKE '%\"{$crm}\"%' ORDER BY ID ASC");

                if ($record3["UF_IS_FOLDER"] == 1) {

                    if (!isset($record3["UF_FOLDER_ID"])) {

                        $titleTaskFolder = $titleTask;

                        $forbidden = '\/:*?"<>|+%!@';
                        $titleTaskFolder  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder);

                        if ($titleTaskFolder[strlen($titleTaskFolder) - 1] == ".") {
                            $titleTaskFolder[strlen($titleTaskFolder) - 1] = " ";
                        }

                        if (isset($record3["PARENT_ID"]) && $record3["PARENT_ID"] != 0) {

                            $parent = $record3["PARENT_ID"];

                            $chain = array();

                            $lastFolder = null;

                            while ($parent) {

                                $recordset123 = $connection->query("SELECT * FROM b_uts_tasks_task INNER JOIN b_tasks ON b_uts_tasks_task.VALUE_ID = b_tasks.ID WHERE VALUE_ID = {$parent}");

                                if ($record123 = $recordset123->fetch()) {

                                    if (!isset($record123["UF_FOLDER_ID"]) && !$record123["UF_FOLDER_ID"] != 0 && !$record123["UF_FOLDER_ID"] != "") {
                                        $chain[] = $record123["VALUE_ID"];
                                    } else {
                                        $lastFolder = $record123["UF_FOLDER_ID"];
                                        break;
                                    }

                                    $parent = $record123["PARENT_ID"];
                                } else {
                                    $parent = null;
                                }
                            }

                            //  $chain[]=$record3["ID"];

                            array_unshift($chain, $record3["ID"]);

                            if (!$lastFolder) {
                                $lastFolder = $record4["UF_CATALOG"];
                            }

                            $commonTaskFolder = \Bitrix\Disk\Folder::getById($lastFolder);

                            if ($commonTaskFolder) {

                                if (is_countable($chain)) {

                                    for ($i = count($chain) - 1; $i >= 0; $i--) {

                                        $recordset123 = $connection->query("SELECT * FROM b_uts_tasks_task INNER JOIN b_tasks ON b_uts_tasks_task.VALUE_ID = b_tasks.ID WHERE VALUE_ID = " . $chain[$i]);

                                        if ($record123 = $recordset123->fetch()) {

                                            $titleTaskFolder1 = $record123["TITLE"];
                                        }

                                        $forbidden = '\/:*?"<>|+%!@';
                                        $titleTaskFolder1  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder1);

                                        if ($titleTaskFolder1[strlen($titleTaskFolder1) - 1] == ".") {
                                            $titleTaskFolder1[strlen($titleTaskFolder1) - 1] = " ";
                                        }

                                        $taskFolder1 = $commonTaskFolder->addSubFolder(array(
                                            'NAME' => $titleTaskFolder1,
                                            'CREATED_BY' => 1
                                        ));

                                        if ($taskFolder1) {
                                            $taskFolderId1 = $taskFolder1->getId();

                                            if (isset($taskFolderId1)) {
                                                $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId1} WHERE VALUE_ID = " . $chain[$i]);
                                            }

                                            $commonTaskFolder = $taskFolder1;
                                        }
                                    }
                                }
                            }

                            $fld = $commonTaskFolder;
                        } else {

                            $commonTaskFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG"]);

                            if ($commonTaskFolder) {
                                $taskFolder = $commonTaskFolder->addSubFolder(array(
                                    'NAME' => $titleTaskFolder,
                                    'CREATED_BY' => 1
                                ));

                                if ($taskFolder) {
                                    $taskFolderId = $taskFolder->getId();

                                    if (isset($taskFolderId)) {
                                        $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId} WHERE VALUE_ID = {$id}");

                                        $fld = $taskFolder;
                                    }
                                }
                            }
                        }
                    } else {

                        $titleTaskFolder = $titleTask;

                        $forbidden = '\/:*?"<>|+%!@';
                        $titleTaskFolder  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder);

                        if ($titleTaskFolder[strlen($titleTaskFolder) - 1] == ".") {
                            $titleTaskFolder[strlen($titleTaskFolder) - 1] = " ";
                        }

                        $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

                        if ($folder) {


                            if ($folder->isDeleted()) {
                                $folder->restore(1);
                            }

                            $folder->rename($titleTaskFolder);

                            if (isset($record3["PARENT_ID"]) && $record3["PARENT_ID"] != 0) {

                                $parent = $record3["PARENT_ID"];

                                $chain = array();

                                $lastFolder = null;

                                while ($parent) {

                                    $recordset123 = $connection->query("SELECT * FROM b_uts_tasks_task INNER JOIN b_tasks ON b_uts_tasks_task.VALUE_ID = b_tasks.ID WHERE VALUE_ID = {$parent}");

                                    if ($record123 = $recordset123->fetch()) {

                                        if (!isset($record123["UF_FOLDER_ID"]) && !$record123["UF_FOLDER_ID"] != 0 && !$record123["UF_FOLDER_ID"] != "") {
                                            $chain[] = $record123["VALUE_ID"];
                                        } else {
                                            $lastFolder = $record123["UF_FOLDER_ID"];
                                            break;
                                        }

                                        $parent = $record123["PARENT_ID"];
                                    } else {
                                        $parent = null;
                                    }
                                }

                                //  $chain[]=$record3["ID"];

                                array_unshift($chain, $record3["ID"]);

                                if (!$lastFolder) {
                                    $lastFolder = $record4["UF_CATALOG"];
                                }

                                $commonTaskFolder = \Bitrix\Disk\Folder::getById($lastFolder);

                                if ($commonTaskFolder) {

                                    if (is_countable($chain)) {

                                        for ($i = count($chain) - 1; $i >= 0; $i--) {

                                            $recordset123 = $connection->query("SELECT * FROM b_uts_tasks_task INNER JOIN b_tasks ON b_uts_tasks_task.VALUE_ID = b_tasks.ID WHERE VALUE_ID = " . $chain[$i]);

                                            if ($record123 = $recordset123->fetch()) {

                                                $titleTaskFolder1 = $record123["TITLE"];
                                            }

                                            $forbidden = '\/:*?"<>|+%!@';
                                            $titleTaskFolder1  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder1);

                                            if ($titleTaskFolder1[strlen($titleTaskFolder1) - 1] == ".") {
                                                $titleTaskFolder1[strlen($titleTaskFolder1) - 1] = " ";
                                            }

                                            $folder->moveTo($commonTaskFolder, 1, true);

                                            if ($taskFolder1) {
                                                $taskFolderId1 = $taskFolder1->getId();

                                                if (isset($taskFolderId1)) {
                                                    $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId1} WHERE VALUE_ID = " . $chain[$i]);
                                                }

                                                $commonTaskFolder = $taskFolder1;
                                            }
                                        }
                                    }
                                }

                                $fld = $commonTaskFolder;
                            } else {

                                $commonTaskFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG"]);

                                if ($commonTaskFolder) {

                                    $folder->moveTo($commonTaskFolder, 1, true);

                                    if ($taskFolder) {
                                        $taskFolderId = $taskFolder->getId();

                                        if (isset($taskFolderId)) {
                                            $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId} WHERE VALUE_ID = {$id}");

                                            $fld = $taskFolder;
                                        }
                                    }
                                }
                            }
                        }

                        /*      $connection->query("UPDATE b_disk_object SET PARENT_ID = ".$record4["UF_CATALOG"]." WHERE ID = ".$record3["UF_FOLDER_ID"]); */

                        //    $href = "";

                        //    $sql5 = "UPDATE b_uts_tasks_task SET UF_CATALOG_HREF = '{$href}' WHERE VALUE_ID = {$id}";

                        //   $connection->query($sql5);

                        $fld = $folder;
                    }


                    if ($fld) {

                        $users = array();

                        /* $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
                    
                    while ($record12 = $recordset12->fetch()) {
                        $users[]=$record12["USER_ID"];
                    }*/

                        $recordset12 = $connection->query("SELECT * FROM b_uts_tasks_task WHERE VALUE_ID = {$id}");

                        while ($record12 = $recordset12->fetch()) {

                            $users = unserialize($record12["UF_TASK_FOLDER_MEMBERS"]);

                            if (!$users) {
                                $users = array();
                            }
                        }

                        $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();

                        $accessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

                        $newRights = array();

                        $fd = $fld->getId();

                        $newUsers = array();

                        /*    $sql = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 2 AND ENTITY_ID = {$idDeal}";

                        $recordset = $connection->query($sql);
                    
                        while ($record = $recordset->fetch()) {
                            $newUsers[] = $record["USER_ID"];
                        }*/

                        $sql = "SELECT * FROM b_crm_deal INNER JOIN b_uts_crm_deal ON b_crm_deal.ID = b_uts_crm_deal.VALUE_ID WHERE b_crm_deal.ID = {$idDeal}";

                        $recordset = $connection->query($sql);

                        if ($record = $recordset->fetch()) {

                            //         $newUsers[] = $record["ASSIGNED_BY_ID"];

                            $dealCatalog = $record["UF_CATALOG"];

                            $sql = "SELECT * FROM b_disk_sharing WHERE LINK_OBJECT_ID IS NOT NULL AND REAL_OBJECT_ID = {$dealCatalog}";

                            $recordset = $connection->query($sql);

                            while ($record = $recordset->fetch()) {
                                $newUsers[] = $record["CREATED_BY"];
                            }
                        }

                        /* $arMessageFields = array(
                            "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                            "FROM_USER_ID" => 5,
                            "TO_USER_ID" =>  5,
                            "NOTIFY_MESSAGE" =>  "_".print_r($users, true)."_".print_r($newUsers, true), 
                            "NOTIFY_MESSAGE_OUT" => "_".print_r($users, true)."_".print_r($newUsers, true), 
                            "NOTIFY_MODULE" => "bizproc",
                            "NOTIFY_EVENT" => "activity"
                        );
                        
                        CIMNotify::Add($arMessageFields);*/

                        ///////////////////////////// status //////////////////////////////////////////////////////////////////// $idDeal

                        $closedDeal = 0;

                        $sql5 = "SELECT * FROM b_crm_deal WHERE ID = {$idDeal}";

                        $recordset5 = $connection->query($sql5);

                        if ($record5 = $recordset5->fetch()) {
                            if ($record5["CLOSED"] == "Y") {
                                $closedDeal = 1;
                            }
                        }


                        //    if($closedDeal == 0) {

                        $sql5 = "SELECT * FROM b_disk_sharing WHERE LINK_OBJECT_ID IS NOT NULL AND REAL_OBJECT_ID = " . $record4["UF_CATALOG"];

                        $recordset5 = $connection->query($sql5);

                        while ($record5 = $recordset5->fetch()) {
                            $newUsers[] = $record5["CREATED_BY"];
                        }

                        $errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();

                        if ($zombie == "Y" || $detach == 1) {

                            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

                            $recordset5 = $connection->query($sql5);

                            while ($record5 = $recordset5->fetch()) {

                                foreach ($users as $user) {
                                    if ($user == $record5["CREATED_BY"]) {
                                        $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                                        if ($sourceObject) {

                                            //  $sourceObject->markDeleted($record5["CREATED_BY"]);
                                        }
                                    }
                                }
                            }
                        } else {

                            foreach ($users as $user) {

                                $est = false;

                                foreach ($newUsers as $us) {
                                    if ($us == $user) {
                                        $est = true;
                                    }
                                }


                                if (!$est) {

                                    $arMessageFields = array(
                                        "NOTIFY_TYPE" => IM_NOTIFY_FROM,
                                        "FROM_USER_ID" => 5,
                                        "TO_USER_ID" =>  5,
                                        "NOTIFY_MESSAGE" =>  "_" . $user,
                                        "NOTIFY_MESSAGE_OUT" => "_" . $user,
                                        "NOTIFY_MODULE" => "bizproc",
                                        "NOTIFY_EVENT" => "activity"
                                    );

                                    //         CIMNotify::Add($arMessageFields);

                                    /* Bitrix\Disk\Sharing::connectToUserStorage(
                                        $user, array(
                                            'SELF_CONNECT' => true,
                                            'CREATED_BY' => $user,
                                            'REAL_OBJECT' => $fld,
                                        ), $errorCollection
                                    );*/
                                }
                            }
                        }

                        $users = array();

                        $sql5 = "SELECT * FROM b_disk_sharing WHERE LINK_OBJECT_ID IS NOT NULL AND REAL_OBJECT_ID = " . $record4["UF_CATALOG"];

                        $recordset5 = $connection->query($sql5);

                        while ($record5 = $recordset5->fetch()) {
                            $users[] = $record5["CREATED_BY"];
                        }

                        $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

                        $recordset5 = $connection->query($sql5);

                        while ($record5 = $recordset5->fetch()) {
                            $est  = false;

                            foreach ($users as $user) {

                                if ($user == $record5["CREATED_BY"]) {
                                    $est = true;

                                    break;
                                }
                            }

                            $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                            if ($sourceObject) {

                                if ($est) {


                                    /* $arMessageFields = array(
                                        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                                        "FROM_USER_ID" => 5,
                                        "TO_USER_ID" =>  5,
                                        "NOTIFY_MESSAGE" =>  print_r($users, true), 
                                        "NOTIFY_MESSAGE_OUT" => print_r($users, true), 
                                        "NOTIFY_MODULE" => "bizproc",
                                        "NOTIFY_EVENT" => "activity"
                                    );
                                    
                                    CIMNotify::Add($arMessageFields);*/

                                    // $sourceObject->markDeleted($record5["CREATED_BY"]);
                                } else {
                                    //  $sourceObject->rename($titleTaskFolder);
                                }
                            }
                        }


                        /*  } else {
                        $sql51 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";
            
                        $recordset51 = $connection->query($sql51);
                    
                        while ($record51 = $recordset51->fetch()) {
                            $sourceObject = \Bitrix\Disk\BaseObject::loadById($record51["LINK_OBJECT_ID"]);

                            if($sourceObject) {
                                $sourceObject->markDeleted($record51["CREATED_BY"]);
                            }
                        }
                    } */

                        ///////////////////////////// status ////////////////////////////////////////////////////////////////////

                        $sql = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 2 AND ENTITY_ID = {$idDeal}";

                        $recordset = $connection->query($sql);

                        while ($record = $recordset->fetch()) {
                            $users[] = $record["USER_ID"];
                        }

                        $sql = "SELECT * FROM b_crm_deal WHERE ID = {$idDeal}";

                        $recordset = $connection->query($sql);

                        if ($record = $recordset->fetch()) {
                            $users[] = $record["ASSIGNED_BY_ID"];
                            $users[] = $record["CREATED_BY"];
                        }

                        /*   foreach($users as $user) {
                            if($user != "") {
                                $newRights[]=    array(
                                    'ACCESS_CODE' =>  "U".$user,
                                    'TASK_ID' =>  $accessTaskId,
                                );
                            }
                        }*/

                        $newRights[] =    array(
                            'ACCESS_CODE' =>  "AU",
                            'TASK_ID' =>  $accessTaskId,
                        );


                        $rightsManager->delete($fld);
                        $rightsManager->set($fld, $newRights);

                        $dealCatalog = $record4["UF_CATALOG"];

                        $dealFolder = \Bitrix\Disk\Folder::getById($dealCatalog);

                        /////////////////////////////////////////////////////////////////////// $fld

                        $gp = $record3["GROUP_ID"];


                        if ($gp && $gp != 0) {


                            $fd = $fld->getId();

                            $sql90 = "SELECT * FROM  b_disk_storage WHERE ENTITY_TYPE = 'Bitrix\\\\Disk\\\\ProxyType\\\\Group' AND ENTITY_ID = {$gp}";

                            $recordset90  = $connection->query($sql90);

                            if ($record90 = $recordset90->fetch()) {

                                $gpRoot = \Bitrix\Disk\BaseObject::loadById($record90["ROOT_OBJECT_ID"]);

                                if ($gpRoot) {

                                    $gpRootId = $gpRoot->getId();

                                    $sql111 = "SELECT * FROM  b_disk_object WHERE REAL_OBJECT_ID = {$fd} AND PARENT_ID = {$gpRootId}";

                                    $recordset111 = $connection->query($sql111);

                                    if ($record111 = $recordset111->fetch()) {

                                        if ($record111["NAME"] != $titleTaskFolder) {

                                            $sourceObject = \Bitrix\Disk\BaseObject::loadById($record111["ID"]);
                                            if ($sourceObject)
                                                $sourceObject->rename($titleTaskFolder);
                                        }
                                    } else {

                                        $sql999 = "SELECT * FROM  b_sonet_group WHERE ID = {$gp}";

                                        $recordset999  = $connection->query($sql999);

                                        if ($record999 = $recordset999->fetch()) {

                                            $owner = $record999["OWNER_ID"];
                                        }

                                        if ($owner) {

                                            $inGpFolder = $gpRoot->addSubFolder(array(
                                                'NAME' => $titleTaskFolder,
                                                'CREATED_BY' => $owner
                                            ));

                                            if ($inGpFolder) {

                                                $gid = $inGpFolder->getId();

                                                $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$fd} WHERE ID = {$gid}");
                                            }
                                        }
                                    }
                                }
                            }

                            $sql222 = "SELECT b_disk_object.ID as id, b_disk_storage.ENTITY_ID as e_id FROM  b_disk_object INNER JOIN b_disk_storage ON b_disk_object.PARENT_ID = b_disk_storage.ROOT_OBJECT_ID
                            WHERE REAL_OBJECT_ID = {$fd} AND ENTITY_TYPE = 'Bitrix\\\\Disk\\\\ProxyType\\\\Group'";

                            $recordset222 = $connection->query($sql222);

                            while ($record222 = $recordset222->fetch()) {
                                if ($record222["e_id"] != $gp) {
                                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record222["id"]);

                                    if ($sourceObject) {
                                        $sourceObject->markDeleted(1);
                                    }
                                }
                            }
                        }

                        /////////////////////////////////////////////////////////////////////// $fld

                        if ($dealFolder) {

                            $fd = $fld->getId();

                            /*  $tskInFolder = $dealFolder->getChild( 
                            array( 
                                '=NAME' => $titleTaskFolder,  
                                'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER 
                            ) 
                        ); 

                        if($tskInFolder) {
                            $tid = $tskInFolder->getId();
                        } else {

                            $tskInFolder = $dealFolder->addSubFolder(    array( 
                                'NAME' => $titleTaskFolder,  
                                'CREATED_BY' => 1 
                            )); 

                            if($tskInFolder) {
                                $tid = $tskInFolder->getId();
                            }
                        }*/


                            /* if($tid) {
                            $sql66 = "SELECT * FROM b_disk_object WHERE ID = {$tid}";
        
                            $recordset66 = $connection->query($sql66);
                        
                            if ($record66 = $recordset66->fetch()) {
                                if($record66["REAL_OBJECT_ID"] != $fd ) {
                                    $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$fd} WHERE ID = {$tid}");
                                }
                            }
                        }*/

                            /*   $sql88 = "SELECT * FROM b_disk_object WHERE REAL_OBJECT_ID = {$fd}";
        
                        $recordset88 = $connection->query($sql88);
                    
                        while ($record88 = $recordset88->fetch()) {
                            if($record88["ID"] != $tid && $record88["ID"] != $fd && $record88["STORAGE_ID"] == 488) {
                                $sourceObject = \Bitrix\Disk\BaseObject::loadById($record88["ID"]);

                                if($sourceObject) {
                                    $sourceObject->markDeleted(1);
                                }
                            }
                        }*/

                            ////////////////////////////////////////
                            $idFolder = $fld->getChild(
                                array(
                                    '=NAME' => '!ИД_СДЕЛКИ',
                                    'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER
                                )
                            );

                            if ($idFolder) {
                                $iFid = $idFolder->getId();
                            } else {

                                /*  $idFolder = $fld->addSubFolder(    array( 
                                'NAME' => '!ИД_СДЕЛКИ',  
                                'CREATED_BY' => 1
                            )); */

                                if ($idFolder) {
                                    $iFid = $idFolder->getId();
                                }
                            }

                            if ($iFid) {

                                $idDeal =  $dealFolder->getChild(
                                    array(
                                        '=NAME' => '!ИД',
                                        'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER
                                    )
                                );

                                if ($idDeal) {

                                    $idDealId = $idDeal->getId();

                                    $sql55 = "SELECT * FROM b_disk_object WHERE ID = {$iFid}";

                                    $recordset55 = $connection->query($sql55);

                                    if ($record55 = $recordset55->fetch()) {
                                        if ($record55["REAL_OBJECT_ID"] != $idDealId) {
                                            $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$idDealId} WHERE ID = {$iFid}");

                                            $sql66 = "SELECT * FROM b_disk_sharing WHERE LINK_OBJECT_ID = {$iFid} AND LINK_STORAGE_ID = 488 AND REAL_STORAGE_ID = 488";

                                            $recordset66 = $connection->query($sql66);

                                            if (!($record66 = $recordset66->fetch())) {
                                                $connection->query("INSERT INTO b_disk_sharing (CREATED_BY, FROM_ENTITY, TO_ENTITY, LINK_STORAGE_ID, LINK_OBJECT_ID, REAL_OBJECT_ID, REAL_STORAGE_ID,
                                            DESCRIPTION, CAN_FORWARD, STATUS, TYPE, TASK_NAME, IS_EDITABLE)
                                            VALUES (485, 'U485', 'U485', 488, {$iFid}, {$idDealId}, 488, '', 0, 3, 2, 'disk_access_read', 0 )");
                                            } else if ($record66["REAL_OBJECT_ID"] != $idDealId) {
                                                $connection->query("UPDATE b_disk_sharing SET REAL_OBJECT_ID = {$idDealId} WHERE ID=" . $record66["ID"]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {


                    $users = array();

                    /* $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
                
                while ($record12 = $recordset12->fetch()) {
                    $users[]=$record12["USER_ID"];
                }*/

                    $recordset12 = $connection->query("SELECT * FROM b_uts_tasks_task WHERE VALUE_ID = {$id}");

                    while ($record12 = $recordset12->fetch()) {
                        $users = unserialize($record12["UF_TASK_FOLDER_MEMBERS"]);

                        if (!$users) {
                            $users = array();
                        }
                    }

                    $fld =  \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

                    if ($fld) {

                        $fd = $fld->getId();

                        $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

                        $recordset5 = $connection->query($sql5);

                        while ($record5 = $recordset5->fetch()) {

                            foreach ($users as $user) {
                                if ($user == $record5["CREATED_BY"]) {
                                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                                    if ($sourceObject) {

                                        $sourceObject->markDeleted($record5["CREATED_BY"]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if ($zombie == "Y" || $detach == 1) {
        $users = array();

        /* $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
    
    while ($record12 = $recordset12->fetch()) {
        $users[]=$record12["USER_ID"];
    }*/

        $recordset12 = $connection->query("SELECT * FROM b_uts_tasks_task WHERE VALUE_ID = {$id}");

        while ($record12 = $recordset12->fetch()) {
            $users = unserialize($record12["UF_TASK_FOLDER_MEMBERS"]);

            if (!$users) {
                $users = array();
            }
        }

        $fld =  \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

        if ($fld) {

            $fd = $fld->getId();

            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

            $recordset5 = $connection->query($sql5);

            while ($record5 = $recordset5->fetch()) {

                foreach ($users as $user) {
                    if ($user == $record5["CREATED_BY"]) {
                        $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                        if ($sourceObject) {

                            //   $sourceObject->markDeleted($record5["CREATED_BY"]);
                        }
                    }
                }
            }
        }
    } else {
        $users = array();

        /*  $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
    
    while ($record12 = $recordset12->fetch()) {
        $users[]=$record12["USER_ID"];
    }*/

        $recordset12 = $connection->query("SELECT * FROM b_uts_tasks_task WHERE VALUE_ID = {$id}");

        while ($record12 = $recordset12->fetch()) {
            $users = unserialize($record12["UF_TASK_FOLDER_MEMBERS"]);

            if (!$users) {
                $users = array();
            }
        }


        $fld =  \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

        if ($fld) {

            $fd = $fld->getId();

            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";

            $recordset5 = $connection->query($sql5);

            while ($record5 = $recordset5->fetch()) {

                $userEst = false;

                foreach ($users as $user) {

                    if ($user == $record5["CREATED_BY"]) {
                        $userEst = true;
                    }
                }

                if (!$userEst) {
                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);

                    if ($sourceObject) {

                        //  $sourceObject->markDeleted($record5["CREATED_BY"]);
                    }
                }
            }
        }
    }
}
