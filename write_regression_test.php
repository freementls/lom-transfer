<?php
include('O.php');

function ok($cond, $label) {
    print($label . ': ' . ($cond ? "OK" : "FAIL") . PHP_EOL);
    if(!$cond) {
        exit(1);
    }
}

$O = new O('write_test_fixed.xml');
$O->debug(false);

for($counter = 0; $counter < 6; $counter++) {
    if($counter % 2 === 0) {
        $O->new_('<alternating_write' . $counter . '></alternating_write' . $counter . '>');
        ok(strpos($O->code(), '<alternating_write' . $counter . '></alternating_write' . $counter . '>') !== false, 'append alternating_write' . $counter);
    } else {
        $O->new_('<alternating_write' . $counter . '></alternating_write' . $counter . '>', $O->enc('simple_write' . $counter));
        ok(strpos($O->code(), '<simple_write' . $counter . '><alternating_write' . $counter . '></alternating_write' . $counter . '></simple_write' . $counter . '>') !== false, 'nested alternating_write' . $counter);
    }
}

$O->new_('<complex1>text1<complex2>text2</complex2><complex3>text3</complex3>text4</complex1>', $O->enc('alternating_write4'));
ok(strpos($O->code(), '<alternating_write4><complex1>text1<complex2>text2</complex2><complex3>text3</complex3>text4</complex1></alternating_write4>') !== false, 'complex1 inserted into alternating_write4');

$O->__($O->enc('alternating_write2'), 'set text1');
ok(strpos($O->code(), '<alternating_write2>set text1</alternating_write2>') !== false, 'set alternating_write2 text');

$matches = $O->get_tagged('.alternating_write4_complex1_complex2');
ok(sizeof($matches) === 1 && $matches[0][0] === '<complex2>text2</complex2>', 'select nested complex2 after writes');

print("Regression sequence completed.\n");
?>
