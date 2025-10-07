<?php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function order_statuses(){
    return ['pending','confirmed','shipped','delivered','canceled'];
}
?>
