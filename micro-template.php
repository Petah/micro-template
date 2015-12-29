<?php
class Renderer {
    private $view;
    private $data;
    private $layout;
    private $body;
    
    public function __construct($view, $data, $layout) {
        $this->view = $view;
        $this->data = $data;
        $this->layout = $layout;
    }

    public function render() {
        foreach ($this->data as $key => $value) {
            $$key = new Escaper($value);
        }
        ob_start();
        require $this->view;
        $this->body = ob_get_clean();
        
        ob_start();
        require $this->layout;
        return ob_get_clean();
    }
    
    public function body() {
        return $this->body;
    }
    
    public function extend($layout) {
        foreach ($this->data as $key => $value) {
            $$key = new Escaper($value);
        }
        $this->body = ob_get_clean();
        ob_start();
        require $layout;
    }
    
    public function partial($view) {
        ob_start();
        require $view;
        return ob_get_clean();
    }
    
}

class Escaper implements \ArrayAccess, \IteratorAggregate, \Countable {
    private $filters = [];
    private $value;
    
    public function __construct($value) {
        $this->value = $value;
    }
    
    public function html() {
        $this->filters[] = 'htmlspecialchars';
        return $this;
    }
    
    public function attr() {
        $this->filters[] = function($value) {
            return htmlspecialchars($value, ENT_QUOTES);
        };
        return $this;
    }
    
    public function json() {
        $this->filters[] = 'json_encode';
        return $this;
    }
    
    public function plain() {
        $this->filters[] = function($value) {
            return preg_replace('/[^a-z]/', '', $value);
        };
        return $this;
    }
    
    public function __toString() {
        if (empty($this->filters)) {
            $this->plain();
        }
        $value = $this->value;
        foreach ($this->filters as $filter) {
            $value = $filter($value);
        }
        return $value;
    }
    
    public function __call($name, $arguments) {
        if (!is_object($this->data)) {
            throw (new \Exception('Escaped data is not an object.'))->setContext($this);
        }
        return new static(call_user_func_array([$this->data, $name], $arguments));
    }

    public function __get($name) {
        if (!is_object($this->data)) {
            throw (new \Exception('Escaped data is not an object.'))->setContext($this);
        }
        return new static($this->data->$name);
    }

    private function checkArray() {
        if (!is_array($this->data)) {
            throw (new \Exception('Escaped data is not an array.'))->setContext($this);
        }
    }

    public function offsetSet($key, $value) {
        $this->checkArray();
        $this->data[$key] = $value;
    }

    public function offsetExists($key) {
        $this->checkArray();
        return isset($this->data[$key]);
    }

    public function offsetUnset($key) {
        $this->checkArray();
        unset($this->data[$key]);
    }

    public function offsetGet($key) {
        $this->checkArray();
        return new static($this->data[$key]);
    }

    public function getIterator() {
        foreach ($this->data as $key => $value) {
            yield new static($key) => new static($value);
        }
    }

    public function count() {
        return count($this->data);
    }
}

echo (new Renderer('view.php', [
    'html' => '<script>alert("html");</script>',
    'attr' => '" onclick="alert(\'attr\')',
    'js' => 'alert("js")',
    'plain' => '!@#$%^&W$EDFRU&*y6g7fds^H*:"',
], 'inner-layout.php'))->render();

echo microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
