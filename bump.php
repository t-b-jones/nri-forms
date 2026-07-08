<?php
$new = $argv[1] ?? exit("Usage: php bump.php 1.1.2\n");
foreach (
    [
        'pkg_nriforms.xml',
        'com_nriforms/nriforms.xml',
        'plg_fields_nriinputs/nriinputs.xml',
        'plg_system_nriforms/nriforms.xml',
        'plg_task_nriforms/nriforms.xml'
    ] as $f
) {
    file_put_contents($f, preg_replace('~<version>[^<]+</version>~', "<version>$new</version>", file_get_contents($f)));
    echo "$f -> $new\n";
}
