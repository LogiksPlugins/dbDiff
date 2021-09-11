<?php
if(!defined('ROOT')) exit('No direct script access allowed');

checkServiceAccess();

handleActionMethodCalls();

function _service_dbcompare() {
    if(!isset($_REQUEST['db1']) || !isset($_REQUEST['db2'])) {
        echo "Database Keys Missing";
        exit();
    }
    if(!isset($_REQUEST['excludes'])) $_REQUEST['excludes'] = "z,z0,z1,z2,z3,z4,z5,temp";
    
    $db1 = $_REQUEST['db1'];
    $db2 = $_REQUEST['db2'];
    $excludes = explode(",", $_REQUEST['excludes']);
    
    // $finalResults = compareTables("tmp2_silkerp2", "tmp2_apple_live", [
//             "z", "z0", "z1", "z2", "z3", "z4", "temp_", 
//             "accounts", "assets", "simpleaccounts", "quotations", "project",
            
//             "user",
            
//             "data_accounts_ledgers", "do_accounts_ledgercodes", "grievance_tbl", 
//             "hr_interview", "hr_interview_round", "hr_jd", "hr_recruitment", "hr_resume",
//             "hr_leaves", "hr_leaves_summary",
//             "hr_siteplan", "hr_timesheet",
//             "issues_tbl",
//         ]);
    $finalResults = compareTables($db1, $db2, $excludes);
    printArray($finalResults);
}

function _service_sqlalterquery() {
    if(!isset($_REQUEST['db1']) || !isset($_REQUEST['db2'])) {
        echo "Database Keys Missing";
        exit();
    }
    if(!isset($_REQUEST['excludes'])) $_REQUEST['excludes'] = "z,z0,z1,z2,z3,z4,z5,temp";
    
    $db1 = $_REQUEST['db1'];
    $db2 = $_REQUEST['db2'];
    $excludes = explode(",", $_REQUEST['excludes']);
    
    $finalResults = compareTables($db1, $db2, $excludes);
    $sqlQuery = generateTableAlterSQL($db1, $finalResults['columns_difference']);
    echo implode("\n<br>", $sqlQuery);
}





function compareTables($db1, $db2, $excludeTablesPrefix = ["z", "z0", "z1", "z2", "z3", "z4", "temp"]) {
    $finalResults = ["table_difference"=>[], "columns_difference"=>[]];
    $tables1 = _db($db1)->get_tableList();
    $tables2 = _db($db2)->get_tableList();
    
    foreach($tables1 as $a=>$tbl) {
        $tblArr = explode("_", $tbl);
        if(in_array($tblArr[0], $excludeTablesPrefix) || in_array($tbl, $excludeTablesPrefix)) {
            unset($tables1[$a]);
        }
    }
    foreach($tables2 as $a=>$tbl) {
        $tblArr = explode("_", $tbl);
        if(in_array($tblArr[0], $excludeTablesPrefix) || in_array($tbl, $excludeTablesPrefix)) {
            unset($tables2[$a]);
        }
    }
    
    $tables1 = array_values($tables1);
    $tables2 = array_values($tables2);
    
    $diffTables = array_diff($tables1, $tables2);
    if(count($diffTables)>0) {
        $finalResults["table_difference"] = $diffTables;
    }
    
    $colList1 = [];
    $colList2 = [];
    foreach($tables1 as $tbl) {
        $cols = _db($db1)->get_columnList($tbl);
        foreach($cols as $col) {
            $colList1[] = "{$tbl}.{$col}";
        }
    }
    foreach($tables2 as $tbl) {
        $cols = _db($db2)->get_columnList($tbl);
        foreach($cols as $col) {
            $colList2[] = "{$tbl}.{$col}";
        }
    }
    
    $diffColumns = array_diff($colList1, $colList2);
    if(count($diffColumns)>0) {
        $finalResults["columns_difference"] = $diffColumns;
    }
    
    return $finalResults;
}

function generateTableAlterSQL($db1, $columnList) {
    if(!$columnList) return [];
    $sqlQuery = [];
    
    $tempTables = [];
    foreach($columnList as $column) {
        $tbl = current(explode(".", $column));
        $colName = explode(".", $column);
        $colName = end($colName);
        
        if(!isset($tempTables[$tbl])) {
            $colList = _db($db1)->get_columnList($tbl, false);
            $tempTables[$tbl] = [
                    "defination"=>$colList,
                    "columns"=>array_keys($colList)
                ];
        }
        if(isset($tempTables[$tbl]['defination'][$colName])) {
            $sql = "";
            $colDefn = $tempTables[$tbl]['defination'][$colName];
            if($colDefn[2]=="NO") {
                $sql = "ALTER TABLE {$tbl} ADD {$colDefn[0]} {$colDefn[1]} NOT NULL";
            } else {
                $sql = "ALTER TABLE {$tbl} ADD {$colDefn[0]} {$colDefn[1]}";
            }
            
            if(strlen($colDefn[4])>0) {
                $sql .= " DEFAULT '{$colDefn[4]}'";
            }
            
            $colList = $tempTables[$tbl]['columns'];
            $colIndex = array_search($colName, $colList);
            if($colIndex>0) {
                $colIndex = $colIndex-1;
            }
            $sql .= " AFTER {$colList[$colIndex]};";
            
            $sqlQuery[] = $sql;
        }
    }
    
    return $sqlQuery;
}

function findDatabaseAnamoly($db1, $excludeTablesPrefix = ["z", "z0", "z1", "z2", "z3", "z4", "temp_"]) {
    $finalResults = [
            "too_many_text_cols"=> [],
            "too_many_cols"=> [],
        ];
    $tables1 = _db($db1)->get_tableList();
    
    foreach($tables1 as $tbl) {
        $tblArr = explode("_", $tbl);
        if(!in_array($tblArr[0], $excludeTablesPrefix) || in_array($tbl, $excludeTablesPrefix)) {
            $colList = _db($db1)->get_columnList($tbl, false);
            
            $txtCounter = 0;
            foreach($colList as $col=>$defn) {
                if($defn[1]=="text" || $defn[1]=="tinytext" || $defn[1]=="longtext" || $defn[1]=="mediumtext") {
                    $txtCounter++;
                } elseif($defn[1]=="blob" || $defn[1]=="tinyblob" || $defn[1]=="longblob" || $defn[1]=="mediumblob") {
                    $txtCounter++;
                }
            }
            if($txtCounter>2) {
                $finalResults['too_many_text_cols'][] = $tbl." - ".$txtCounter;
            }
            
            if(count($colList)>30) {
                $finalResults['too_many_cols'][] = $tbl." - ".count($colList);
            }
        }
    }
    
    return $finalResults;
}

// $finalResults = compareTables("tmp2_silkerp2", "tmp2_apple_live", [
//             "z", "z0", "z1", "z2", "z3", "z4", "temp_", 
//             "accounts", "assets", "simpleaccounts", "quotations", "project",
            
//             "user",
            
//             "data_accounts_ledgers", "do_accounts_ledgercodes", "grievance_tbl", 
//             "hr_interview", "hr_interview_round", "hr_jd", "hr_recruitment", "hr_resume",
//             "hr_leaves", "hr_leaves_summary",
//             "hr_siteplan", "hr_timesheet",
//             "issues_tbl",
//         ]);
// printArray($finalResults);

// $sqlQuery = generateTableAlterSQL("tmp2_silkerp2", $finalResults['columns_difference']);
// echo implode("\n<br>", $sqlQuery)

// $results = findDatabaseAnamoly("tmp2_apple_live", [
//                     "z", "z0", "z1", "z2", "z3", "z4", "temp_",
//                     "kms",
//                 ]);
// printArray($results);
?>