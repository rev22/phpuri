#!/bin/sh

println() {
    printf "%s\n" "$*"
}

warn() {
    println "$0: $*" >&2
}

die() {
    warn "$*"
    exit 1
}

v() {
    r="$1"; shift
    n="$("$@")"
    [ "$n" = "$r" ] || die "Constraint failed: $*: result: $n; expected: $r"
}

get() {
    echo '<?php include "Uri.php"; print Uri::gen("http://hello")->merge("h=1")->merge("h=3")->set("h", 4)->get("h");' |php
}

v "4" get

more() {
    echo '<?php include "Uri.php"; print Uri::gen("http://hello?blue=deep")->merge("h=1")->merge("h=3")->set("blue", "sky")->sub("x/")->sub("metal")->sub("keys");' |php
}

v "http://hello/x/metal/keys?blue=sky&h=3" more

del() {
    echo '<?php include "Uri.php"; print Uri::gen("http://hello?blue=deep")->merge("h=1")->merge("h=3")->set("blue", "sky")->sub("x/")->sub("metal")->sub("keys")->del("blue");' |php
}

v "http://hello/x/metal/keys?h=3" del

del2() {
    echo '<?php include "Uri.php"; print Uri::gen("http://hello?blue=deep")->merge("h=1")->merge("h=3")->set("blue", "sky")->sub("x/")->sub("metal")->sub("keys")->del("blue")->del("h");' |php
}

v "http://hello/x/metal/keys" del2

p() {
    echo '<?php include "Uri.php";'"$1"|php
}

v 'http://hello?blue=deep
http://hello?blue=deep&h=1&b=2
http://hello?blue=deep&b=2' p '
$a = Uri::gen("http://hello?blue=deep");
$b = $a->merge("h=1&b=2");
$c = $b->del("h");
print "$a\n";
print "$b\n";
print "$c\n";
'
