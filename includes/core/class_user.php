<?php

class User {

    public static function user_info($user_id) {
        // vars
        $q = DB::query("SELECT *  FROM users WHERE user_id ='".$user_id."'  LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) 
        {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => (int) $row['plot_id'],
                'first_name' =>  $row['first_name'],
                'last_name' => $row['last_name'],
                'email' =>  $row['email'],
                'phone' => $row['phone'],
                'last_login' => $row['last_login'],

            ];
        } else {
            return [
                'id' => 0,
                'plot_id' => 0,
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'last_login' =>'',

            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }
    
    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) $where[] = "plot_id LIKE '%".$search."%'";
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT * FROM users ".$where." ORDER BY plot_id+0 LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'last_login' => date('Y/m/d',$row['last_login']),
                'updated' => date('Y/m/d', $row['updated'])
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }
    
    // ACTIONS
    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_info($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }
   
   public static function user_edit_update($d = []) {
        // vars
        $user_id    =  0;
        $plot_id    =  0;
        $first_name =  '';
        $last_name  =  '';
        $phone      =  0;
        $email      =  '';
        $last_login =  0;
        $user_id    =  isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $plot_id    =  isset($d['plot_id']) && is_numeric($d['plot_id']) ? $d['plot_id'] : 0;
        $first_name =  isset($d['first_name']) ? $d['first_name'] : '';
        $last_name  =  isset($d['last_name']) ? $d['last_name'] : '';
        $phone      =  isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        $email      =  isset($d['email']) && mb_strpos($d['email'], '@')===false ? '': $d['email'];
        //$last_login =  isset($d['last_login']) ? $d['last_login'] : 0;
        
        // update
        if ($user_id) 
        {
            $set = [];
            $set[] = "plot_id='".$plot_id."'";
            $set[] = "first_name='".$first_name."'";
            $set[] = "last_name='".$last_name."'";
            $set[] = "phone='".$phone."'";
            $set[] = "email='".$email."'";
            //$set[] = "last_login='".$last_login."'";
            $set[] = "updated='".Session::$ts."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."';") or die (DB::error());
        } else {
            DB::query("INSERT INTO users (
                plot_id,
                first_name,
                last_name,
                phone,
                email,
                last_login,
                updated
            ) VALUES (
                '".$plot_id."',
                '".$first_name."',
                '".$last_name."',
                '".$phone."',
                '".$email."',
                '".Session::$ts."',
                '".Session::$ts."'
            );") or die (DB::error());
        }
        // output
        return User::users_fetch(['offset' => $offset]);
    }  
   
}
