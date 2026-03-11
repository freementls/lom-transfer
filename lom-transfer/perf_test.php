<?php
require_once __DIR__ . '/O.php';

function t() { return microtime(true); }
function ms($s,$e){ return round(($e-$s)*1000,2); }
function report($label,$start,$end,$extra='') {
    echo str_pad($label, 42) . ': ' . str_pad(ms($start,$end), 10, ' ', STR_PAD_LEFT) . " ms";
    if($extra !== '') echo "  " . $extra;
    echo PHP_EOL;
}

$fixture = __DIR__ . '/perf_fixture.xml';
if(!file_exists($fixture)) {
    fwrite(STDERR, "Missing perf_fixture.xml\n");
    exit(1);
}

$start = t();
$O = new O($fixture);
$end = t();
report('construct + validate + depth map', $start, $end, 'bytes=' . strlen($O->code));

$cases = array(
    array('top-level repeated tag', function() use ($O) { return $O->get_tagged('world_region'); }),
    array('descendant chain', function() use ($O) { return $O->get_tagged('world_region_zone_entity_stats'); }),
    array('attribute existence', function() use ($O) { return $O->get_tagged('entity@kind'); }),
    array('attribute value subtag combo', function() use ($O) { return $O->get_tagged('entity_meta_name=Entity_42'); }),
    array('indexed tagname selector', function() use ($O) { return $O->get_tagged('world_region[10]_zone[5]_entity[7]_stats'); }),
    array('parent reads', function() use ($O) { return $O->get_tagged_parent('world_region_zone_entity_stats'); }),
);

foreach($cases as $case) {
    list($label, $fn) = $case;
    $start = t();
    $r1 = $fn();
    $mid = t();
    $r2 = $fn();
    $end = t();
    report($label . ' (cold)', $start, $mid, 'matches=' . count($r1));
    report($label . ' (warm)', $mid, $end, 'matches=' . count($r2));
}

$start = t();
$targets = $O->get_tagged('world_region_zone[3]_entity[4]_meta_note');
$mid = t();
$O->__('world_region_zone[3]_entity[4]_meta_note', 'changed-note');
$end = t();
report('warm up write target select', $start, $mid, 'matches=' . count($targets));
report('set() after context warmup', $mid, $end);

$start = t();
$O->new_('<bonus><name>surge</name><value>99</value></bonus>', 'world_region_zone[3]_entity[4]_meta');
$end = t();
report('new_() nested insert', $start, $end);

$start = t();
$check = $O->get_tagged('world_region_zone[3]_entity[4]_meta_bonus_value');
$end = t();
report('post-write descendant read', $start, $end, 'matches=' . count($check));

$start = t();
$valid = $O->validate();
$end = t();
report('validate() after writes', $start, $end, 'result=' . ($valid ? 'true' : 'false'));

$O->dump_total_time_taken();
