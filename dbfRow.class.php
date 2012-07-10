<?php 
/**
 * Gestiona las filas de la Base, clase utilizada por dbfManager para retornar los registros de la base.
 * 
 * @author Carlos Sosa <carlitin@gmail.com>
 * @version 1.0
 * @license GPL
 * @todo Terminar las documentacion de la clase
 */
class dbfRow implements ArrayAccess, Countable,  Iterator {
    private $array;
    private $labels;
    private $it_pos;
    private $new;
    private $pos;
        
    public function __construct( $array, $labels, $new = false) {   
        $this->labels =$labels;
        
        if  ( is_array($array)) 
            $this->array = $array;
        else 
            throw new ErrorException('Datos de entrada no válidos.');  
        
        $this->it_pos = 0;    
        
        if ( $this->array != false) unset($this->array['deleted']);               
        
        $this->new = $new;
    }
    
    private function _isLabel ( $str) {
        return ( in_array(strtolower($str), $this->labels));
    }


    private function _fromLabel ( $str)
    {
        if ( $this->_isLabel($str))
            return array_search( strtolower($str), $this->labels);        
    }
    
    private function _id ( $str)
    {
        if ( !is_numeric($str) && $this->_isLabel($str))
            {
                return $this->_fromLabel($str);
            } else
            {
                return $str;
            } 
    }

    /** 
     * Countable     
     * @return int
     */
    public function count() 
     { 
        if ( $this->array != false)
         return count($this->array); 
        else return 0;
     }
     
     /**
      * Interface Iterator 
      */
     function rewind() {
        $this->it_pos = 0;
    }

    function current() {   
        if ( $this->array != false) return $this[$this->it_pos];
    }

    function key() {        
        return $this->it_pos;
    }

    function next() {   
        if ( $this->it_pos < count($this) )
            ++$this->it_pos;
    }

    function valid() {      
        return ( $this->key() < count($this) );
    }
    
    /**
     * ArrayAccess 
     */
    public function offsetSet($offset, $value) {               
        if (is_null($offset)) {            
                    throw new ErrorException('Método no válido.');
        } else { 
            if ( $this->offsetExists($offset))
            {
                    $this->array[$this->_id($offset)] = $value; 
                    if ( $this->new )
                        {
                            $this->manager[] = $this;
                            $this->pos = count($this);
                        } else $this->manager[$this->pos] = $this;
                    
            }
            else
                throw new ErrorException('Prosición no válida.');
        }        
    }
    
    public function offsetExists($offset) {
       return ( $offset < count($this) || $this->_isLabel($offset) );
    }
    
    public function offsetUnset($offset) {       
       if ( $this->offsetExists($offset) )
           $this[$this->_id($offset)] = NULL;       
       $this->rewind();
    }
    public function offsetGet($offset) {
        if ( $this->offsetExists($offset) )
           return $this->array[$this->_id($offset)];
        else 
            throw new ErrorException('Posición no válida.');
    }
    
    public function getArray ()
    {
        return $this->array;
    }
}