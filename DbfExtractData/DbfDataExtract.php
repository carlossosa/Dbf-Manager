<?php

namespace DbfDatabase {

use ErrorException;    
   
/**
 * DataExtract
 *
 * @author Carlos
 */
class FilterResult implements \Iterator, \Countable{
    
    private $data;
    
    private $ids;
    
    private $iterator_curr;

    public function __construct(DataExtract $data, array $ids) {
        $this->data = $data;
        $this->ids = $ids;
        
        $this->iterator_curr = 0;
    }

    public function count($mode = 'COUNT_NORMAL') {
        return count($this->ids);
    }

    public function current() {
        return $this->data->getRecord($this->ids[$this->iterator_curr]);
    }

    public function key() {
        return $this->iterator_curr;
    }

    public function next() {
        $this->iterator_curr++;
    }

    public function rewind() {
        $this->iterator_curr = 0;
    }

    public function valid() {
        return ($this->iterator_curr<count($this));
    }

    }

/**
 * DataExtract
 *
 * @author Carlos
 */
class DataExtract implements \Iterator, \Countable {
    
    private $filef;
    private $records;
    private $fields;
    private $first_record;
    private $record_length;
    private $record_unpack;
    

    /**
     * 
     * @param string $base_path Path to database DBF
     */
    function __construct( $base_path) {
        if ( !is_readable($base_path)) {
            throw new ErrorException("File {$base_path} is not readable.");
        }
        
        //Open file
        $this->filef = fopen( $base_path, 'r');
        
        //Load headers
        $buffer = fread( $this->filef, 32);
        
        $header = unpack( "VCount/vFirst/vLength", substr($buffer, 4, 8));
        
        $this->records = $header['Count'];
        
        // records
        $this->first_record = $header['First'];
        $this->record_length = $header['Length'];
        
        // fields
        $goon = true;
        $this->record_unpack = '';
        while ($goon && !feof($this->filef)) {
	        $buffer = fread($this->filef,32);
	        if (substr($buffer,0,1)==chr(13)) {$goon=false;}
	        else {
	            $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buffer,0,18));
	            $this->record_unpack .= "A$field[fieldlen]$field[fieldname]/";
	            $this->fields[] = $field['fieldname'];
	        }
        }      
        
        //iterator
        $this->iterator_curr = 0;
    }
    
    public function getFields() {
        return $this->fields;
    }
            
    public function getRecord ( $record_number) {
        
        if (  $record_number >= ($this->records) ) {
            throw new \ErrorException('Not valid record.');
        }
        
        fseek($this->filef, $this->first_record + 1 + ( $this->record_length * $record_number) );
        
        $buffer = fread( $this->filef, $this->record_length);
        
        $record = unpack($this->record_unpack,$buffer);
        
        return $record;
    }

    //iterator
    private $iterator_curr;


    public function current() {
        return $this->getRecord($this->iterator_curr);
    }

    public function key() {
        return $this->iterator_curr;
    }

    public function next() {
        $this->iterator_curr++;
    }

    public function rewind() {
        $this->iterator_curr = 0;
    }

    public function valid() {
        return ($this->iterator_curr  < $this->records);
    }

    //COuntable
    public function count($mode = 'COUNT_NORMAL') {
        return $this->records;
    }
    
    //Filter
    /**
     * 
     * @param mixed $fields
     * @param int $limit
     * @param int $offset
     * @return \DbfDatabase\FilterResult
     */
    public function filter ( $fields, $limit = 0, $offset = 0) {
        $ids = array();
        
        fseek($this->filef, $this->first_record + 1 + ( $this->record_length * $offset) );
        
        $end = count($this) ;
        
        for ( $i=$offset; $i<$end; $i++ ) {
            
            $buffer = fread( $this->filef, $this->record_length);        
            $record = unpack($this->record_unpack,$buffer);
            
            //match                       
            if (is_array($fields)) {                
                $match = true;
                foreach ( $fields as $k=>$v) {
                    if ( $record[$k] != $v)
                        $match = false;
                }
            } else if (is_callable( $fields)) {
                $match = $fields($record);
            }
            
            if ( $match){
                $ids[] = $i;
                
                if ( count($ids) == $limit)
                {
                    break;
                }
            }
        }
        
        return new FilterResult( $this, $ids);
    }
    } 

}