<?php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function order_statuses(){
    return ['pending','confirmed','shipped','delivered','canceled'];
}

function get_setting($key, $default = null){
    try{
        $pdo = db();
        $stmt = $pdo->prepare('SELECT v FROM settings WHERE k = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && array_key_exists('v',$row)) return $row['v'];
    }catch(Exception $e){}
    return $default;
}

function set_setting($key, $value){
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO settings (k, v, updated_at) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)');
    $stmt->execute([$key, (string)$value, $now]);
}

function get_settings_all(){
    $out = [];
    try{
        $pdo = db();
        $stmt = $pdo->query('SELECT k, v FROM settings');
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
            $out[$r['k']] = $r['v'];
        }
    }catch(Exception $e){}
    return $out;
  }
?>
