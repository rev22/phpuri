<?php

# Copyright (c) 2013 Michele Bini

# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

# A KISS-philosophy, transparent, functional, high performance URI representation with fluent methods

# Sample usage:

# Uri::gen("http://www.host.com")->set("a", "x").""  				 # http://www.host.com?a=x
# Uri::gen("http://www.host.com/?a=foo")->merge("a=bar&b=baz")."" 	 # http://www.host.com?a=bar&b=baz
# Uri::gen("http://www.host.com")->set("a", "x")->sub("subpath").""  # http://www.host.com/subpath?a=x
# Uri::gen("http://www.host.com")->set("a", "x")->sub("subpath").""  # http://www.host.com/subpath?a=x
# Uri::gen("http://www.host.com/x?foo=bar")->get("foo")   			 # foo
# Uri::gen("http://www.host.com/x?foo=bar")->del("foo").""			 # http://www.host.com/x

class Uri {
    private $s;
    private $parts;

    public static function gen($s, $query = null) {
        if ($s instanceof Uri) {
            $x = clone $s;
            if ($query != null) return $x->merge($query);
            return $x;
        } else {
            $x = new Uri;
            $x->s = $s;
            if ($query != null) return $x->merge($query);
            return $x;
        }
    }

    public function getQuery() {
        if (isset($this->parts)) {
            if (isset($this->parts["query"])) {
                return $this->parts["query"];
            }
        }
        return null;
    }

    public function setQuery($q) {
        if (!isset($this->parts)) $this->parts = parse_url($this->s);
        $x = clone $this;
        $x->parts["query"] = $q;
        return $x;
    }

    public function merge($query) {
        if (($query == null) || ($query === "")) return $this;
        if (!isset($this->parts)) $this->parts = parse_url($this->s);
        $x = clone $this;
        if (isset($x->parts["query"])) {
            $x->parts["query"] =
                UriQuery::gen($x->parts["query"])->merge($query);
        } else {
            $x->parts["query"] = $query;
        }
        $x->s = null;
        return $x;
    }

    public function set($k, $v) {
        if (!isset($this->parts)) $this->parts = parse_url($this->s);
        $x = clone $this;
        if (isset($x->parts["query"])) {
            $x->parts["query"] = UriQuery::gen($x->parts["query"])->set($k, $v);
        } else {
            $x->parts["query"] = [ $k => $v ];
        }
        $x->s = null;
        return $x;
    }

    public function del($k) {
        if (!isset($this->parts)) $this->parts = parse_url($this->s);
        if (isset($this->parts["query"])) {
            $p = $this->parts["query"];
            $n = UriQuery::gen($p)->del($k);
            if ($p === $n) return $this;
            $x = clone $this;
            $x->s = null;
            $x->parts["query"] = $n;
            return $x;
        } else {
            return $this;
        }
    }

    public function get($k) {
        if (!isset($this->parts)) $this->parts = parse_url($this->s);
        if (isset($this->parts["query"])) {
            $q = $this->parts["query"] = UriQuery::gen($this->parts["query"]);
            return $q->get($k);
        }
        return null;
    }

    public function sub($path) {
        if ($path == "") return $this;
        $x = clone $this;
        if (!isset($x->parts)) $x->parts = parse_url($x->s);
        unset($x->s);
        if (!isset($x->parts["path"])) {
            if (substr($path, 0, 1) == "/") {
                $x->parts["path"] = $path;
            } else {
                $x->parts["path"] = "/" . $path;
            }
            return $x;
        }
        $p = $x->parts["path"];
        if (($p == null) || ($p == "")) {
            if (substr($path, 0, 1) == "/") {
                $p = $path;
            } else {
                $p = "/" . $path;
            }
        } else {
            $p = rtrim($p, "/");
            if (substr($path, 0, 1) == "/") {
                $p = $p . $path;
            } else {
                $p = $p . "/" . $path;
            }            
        }
        $x->parts["path"] = $p;
        return $x;
    }

    # Forces regeneration of the url string
    public function canonical() {
        if (isset($this->s)) {
            $x = clone $this;
            if (!isset($x->parts)) $x->parts = parse_url($x->s);
            if (isset($x->parts["query"])) {
                $x->parts["query"] = UriQuery::gen($x->parts["query"])->canonical();
            }
            unset($x->s);
        }
        return $this;
    }
    # public get($k) { }
    
    public function __toString() {
        if (isset($this->s)) {
            return $this->s;
        } else {
            $parts = $this->parts;
            return $this->s =
                (isset($parts["scheme"])    ? 		 $parts["scheme"] . "://"  :"").
                (isset($parts["user"]) 		? 		 $parts["user"] . ":"      :"").
                (isset($parts["pass"]) 		? 		 $parts["pass"] . "@"      :"").
                (isset($parts["host"]) 		? 		 $parts["host"] 		   :"").
                (isset($parts["port"]) 		? ":" .  $parts["port"] 		   :"").
                (isset($parts["path"]) 		? 		 $parts["path"] 		   :"").
                (isset($parts["query"]) 	? "?" .  $parts["query"] 		   :"").
                (isset($parts["fragment"])  ? "#" .  $parts["fragment"] 	   :"");
        }
    }

    public static function fromParts($scheme = null, $host = null, $port = null, $path = null, $query = null, $fragment = null) {
        $x = new Uri;
        if ($scheme    != null) $x->parts["scheme"]    = $scheme;
        if ($host  	   != null) $x->parts["host"]      = $host;
        if ($port  	   != null) $x->parts["port"]      = $port;
        if ($path  	   != null) $x->parts["path"]      = $path;
        if ($query     != null) $x->parts["query"]     = $query;
        if ($fragment  != null) $x->parts["fragment"]  = $fragment;
        return $x;
    }
}

class UriQuery {
    private $s;
    private $m;
    public static function gen($q) {
        if ($q instanceof self) {
            return clone $q;
        } elseif (is_array($q)) {
            $x = new self;
            $x->m = $q;
            return $x;
        } else {
            $x = new self;
            $x->s = $q;
            return $x;
        }
    }
    public function arr() {
        if (isset($this->m)) return $this->m;
        parse_str($this->s, $m);
        return $this->m = $m;
    }
    public function merge($x) {
        $a = $this->arr();
        $b = self::gen($x)->arr();
        if (empty($a)) {
            if (empty($b)) {
                return $a;
            } else {
                return $b;
            }
        } else {
            if (empty($b)) {
                return $a;
            } else {
                $r = new self;
                $r->m = array_merge($a, $b);
                $r->s = null;
                return $r;
            }
        }
    }
    public function set($k, $v) {
        $m = $this->arr();
        $m[$k] = $v;
        $n = clone $this;
        $n->m = $m;
        $n->s = null;
        return $n;
    }
    public function del($k) {
        $m = $this->arr();
        if (isset($m[$k])) {
            unset($m[$k]);
            if (empty($m)) return null;
            $n = clone $this;
            $n->m = $m;
            $n->s = null;
            return $n;
        } else {
            return $this;
        }
    }
    public function get($k) {
        $m = $this->arr();
        return $m[$k];
    }
    public function canonical() {
        $this->arr();
        unset($this->s);
        return $this;
    }
    public function __toString() {
        if (isset($this->s)) return $this->s;
        return $this->s = http_build_query($this->arr());
    }
}
