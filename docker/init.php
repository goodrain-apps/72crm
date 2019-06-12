<?php

function resultArray($array)
{
    fwrite(STDERR, $array['error']);
    exit(1);
}

function initDB(array $db_config, $username, $password) {
    if (empty($db_config['hostname'])) {
        resultArray(['error' => '请填写数据库主机!']);
    }           
    if (empty($db_config['hostport'])) {
        resultArray(['error' => '请填写数据库端口!']);
    }
    if (preg_match('/[^0-9]/', $db_config['hostport'])) {
        resultArray(['error' => '数据库端口只能是数字!']);
    }
    if (empty($db_config['database'])) {
        resultArray(['error' => '请填写数据库名!']);
    }
    if (empty($db_config['username'])) {
        resultArray(['error' => '请填写数据库用户名!']);
    }
    if (empty($db_config['password'])) {
        resultArray(['error' => '请填写数据库密码!']);
    }        
    if (empty($db_config['prefix'])) {
        resultArray(['error' => '请填写表前缀!']);
    }
    if (preg_match('/[^a-z0-9_]/i', $db_config['prefix'])) {
        resultArray(['error' => '表前缀只能包含数字、字母和下划线!']);
    }
    if (empty($username)) {
        resultArray(['error' => '请填写管理员用户名!']);
    }
    if (empty($password)) {
        resultArray(['error' => '请填写管理员密码!']);
    }
    $database = $db_config['database'];
		unset($db_config['database']);
        $connect = Db::connect($db_config);
        // 检测数据库连接
        try{
            $ret = $connect->execute('select version()');
        }catch(\Exception $e){
            resultArray(['error' => '数据库连接失败，请检查数据库配置！']);
        }
        $check = $connect->execute("SELECT * FROM information_schema.schemata WHERE schema_name='".$database."'");
        if (!$check && !$connect->execute("CREATE DATABASE IF NOT EXISTS `".$database."` default collate utf8_general_ci ")) {
            resultArray(['error' => '没有找到您填写的数据库名且无法创建！请检查连接账号是否有创建数据库的权限!']);
        }
		$db_config['database'] = $database;
        writeDBConfigFile($db_config);
        $C_Patch = substr($_SERVER['SCRIPT_FILENAME'],0,-10);
        $sql = file_get_contents( $C_Patch.'/public/sql/5kcrm.sql');
        $sqlList = parse_sql($sql, 0, ['5kcrm_' => $db_config['prefix']]);
        if ($sqlList) {
            $sqlList = array_filter($sqlList);
            $install_count = count($sqlList);
            session('install_count',$install_count);
            foreach ($sqlList as $k=>$v) {
                $install_now = $k+1;
                session('install_now',$install_now);
                try {
                    $temp_sql = $v.';';
                    Db::connect($db_config)->query($temp_sql);
                } catch(\Exception $e) {
                    // resultArray(['error' => '请启用InnoDB数据引擎，并检查数据库是否有DROP和CREATE权限']);
                    resultArray(['error' => '数据库sql安装出错，请操作数据库手动导入sql文件']);
                }
            }
        } 
        $salt = substr(md5(time()),0,4);
        $password = user_md5(trim($password), $salt, $username);
		//插入信息
        Db::connect($db_config)->query("insert into ".$db_config['prefix']."admin_user (username, password, salt, img, thumb_img, realname, create_time, num, email, mobile, sex, status, structure_id, post, parent_id, type, authkey, authkey_time ) values ( '".$username."', '".$password."', '".$salt."', '', '', '管理员', ".time().", '', '', '".$username."', '', 1, 1, 'CEO', 0, 1, '', 0 )");
        Db::connect($db_config)->query("insert into ".$db_config['prefix']."hrm_user_det (user_id, join_time, type, status, userstatus, create_time, update_time, mobile, sex, age, job_num, idtype, idnum, birth_time, nation, internship, done_time, parroll_id, email, political, location, leave_time ) values ( 1, ".time().", 1, 1, 2, ".time().", ".time().", '".$username."', '', 0, '', 0, '', '', 0, 0, 0, 0, '', '', '', 0 )");
        touch(CONF_PATH . "install.lock"); 
        
}


function writeDBConfigFile(array $data) {
    $code = <<<INFO
    <?php
    return [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '{$data['hostname']}',
        // 数据库名
        'database'        => '{$data['database']}',
        // 用户名
        'username'        => '{$data['username']}',
        // 密码
        'password'        => '{$data['password']}',
        // 端口
        'hostport'        => '{$data['hostport']}',
        // 连接dsn
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '{$data['prefix']}',
        // 数据库调试模式
        'debug'           => true,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 自动读取主库数据
        'read_master'     => false,
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => 'array',
    ];
    
INFO;
    file_put_contents( CONF_PATH.'database.php', $code);
    // 判断写入是否成功
    $config = include CONF_PATH.'database.php';
    if (empty($config['database']) || $config['database'] != $data['database']) {
        return $this->error('[config/database.php]数据库配置写入失败！');
        exit;
    }
    return 1;
}

$db_config['type'] = 'mysql';
$db_config['hostname'] = getenv("MYSQL_HOST");
$db_config['hostport'] = getenv("MYSQL_PORT");
$db_config['database'] = getenv("MYSQL_DATABASE");
$db_config['username'] = getenv("MYSQL_USER");
$db_config['password'] = getenv("MYSQL_PASSWORD");        
$db_config['prefix']   = empty(getenv("MYSQL_PREFIX"))?"5kcrm_":getenv("MYSQL_PREFIX");

initDB($db_config, getenv("CRM_ADMIN_USER"), getenv("CRM_ADMIN_PASSWORD"));