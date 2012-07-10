<?php
/**
 * Manejador de bases dBASE III 
 * @author Carlos Sosa <carlitin@gmail.com>
 * @version 1.0
 * @license GPL
 */
class dbfManager implements ArrayAccess, Countable,  Iterator {
    
    /**
     * Conector a la base dBase
     * @var conector 
     */
    private $db;
    
    /**
     * Posición de Iterator
     * @var int 
     */
    private $it_pos;
    
    /**
     * Arreglo con la estructura de la base de datos
     * @var Array 
     */
    private $struct;
    
    /**
     * Arreglo con los nombre de los campos de Base
     * @var Array 
     */
    private $labels;
   
    /**
     * Modo de apertura de la base READONLY o READWRITE
     * @var int 
     */
    public static $READONLY = 0;
    
    /**
     * @see dbfManager::READONLY
     * @var int 
     */
    public static $READWRITE = 2;
    

    /**
     * Constructor para la Clase dbfManager
     * 
     * @param string $path Ruta de la base de datos en el Sistema de Ficheros
     * @param int $mode Modo de apertura 
     * @param array $struct Estructura de la base, necesario para crear nuevas bases.
     * @throws ErrorException 
     * 
     * Ejemplo de la creación de una base nueva y de una estructura :
     * <?php    
     *  $Salida = new dbfManager( $path, 2, array (
     *                                              array ( 'COD', 'C', 2),     
     *                                              array ( 'MODO', 'C', 3),
     *                                              array ( 'CI_NUM', 'C', 15),       
     *                                              array ( 'URL', 'C', 200),       
     *                                              array ( 'IMPORTE', 'N', 8, 2),       
     *                                              array ( 'DIRECC' ,'C', 80),       
     *                                              array ( 'DEUDA',  'N', 8,2)
     *                                         )       
     *                              );
     * ?>
     * 
     * Para informacion de las estructuras vea http://es.php.net/manual/es/function.dbase-create.php.
     */
    public function __construct( $path, $mode = self::READWRITE, $struct = array() ) {
        // Comprobar si es una nueva o una existente
        if (file_exists( $path))
            {
                // Comprobar permisos de lectura
                if (is_readable($path))
                    {
                        $this->db = $this->_loadDB($path, $mode);
                        if ( !$this->db)
                            throw new ErrorException('Error al cargar '.$path.'.');
                    }
                else                    
                    throw new ErrorException('El origen '.$path.' no puede ser leído.');
            } else {
                // Si es una nueva necesitaremos de una estructura para la creación
                if ( count($struct) < 1)
                    throw new ErrorException('Error al crear la Base no se econtraron datos de la Estructura.');
                
                // Verificar los permisos de escritura
                if (is_writable(dirname($path)))
                    {
                        $this->db = dbase_create( $path, $struct);
                        if ( !$this->db)
                            throw new ErrorException('Error al crear la Base en '.$path.'.');
                    }
                else 
                    throw new ErrorException('Permisos insuficientes en la ruta de destino.');
            }
            
            // Incializamos variables
            $this->it_pos = 0;
            $this->_struct();
    }
        
    public function __destruct() {
        // Es recomendado cerrar la base tras su uso, sobre todo en caso de escrituras a la misma.
        dbase_close($this->db);
    }

    /**
     * Analiza la base de datos 
     */
    private function _struct () {
        $this->struct = dbase_get_header_info($this->db);
        foreach ( $this->struct as $st)
            $this->labels[] = strtolower ($st['name']);
    }       
       
    private function _loadDB ( $path, $mode)
    {
        return dbase_open( $path, $mode);
    }
    
    /**
     * Returna un registro de la base para luego ser almacenado en la misma.
     * 
     * @return \dbfRow 
     */
    public function newReg ()
    {
        return new dbfRow( array_fill(0, count($this->labels), ''), $this->labels, true);        
    }
    
    /**
     * Buscar un registro en la Base
     * 
     * @param string $campo
     * @param mixed $valor
     * @return ArrayObject
     * @throws ErrorException 
     */
    public function search ( $campo, $valor)
    {
        if ( in_array(strtolower($campo), $this->labels) || 
                ( is_numeric($campo) && $campo < count($this->labels) ))
            foreach ( $this as $row)
            {
                if ( trim($row[$campo]) == trim($valor) ) return $row;
            }
            else 
                throw ErrorException('Campo no válido.');
    }
    
    /**
     * Arreglo con los nombre de los campos de la base
     * 
     * @return Array 
     */
    public function getLabels() {
        return $this->labels;
    }

    /** 
     * Countable     *
     * @return int
     */
    public function count() 
     { 
         return dbase_numrecords($this->db); 
     }
     
     /**
      * Interface Iterator 
      */
     function rewind() {
        $this->it_pos = 0;
    }

    function current() {       
        return $this[$this->it_pos];
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
    public function offsetSet($offset,  $value) {   
        if ( !(is_array($value)) || !$value )
        {
            throw new ErrorException('Registro no válido.');
        }
        
        if (is_null($offset)) {
            if ( !dbase_add_record( $this->db, $value))
                    throw new ErrorException('Error al guardar el registro.');
        } else {                        
            if ( $offset < count($this) )
                if ( !dbase_replace_record( $this->db, $value, $offset+1))
                        throw new ErrorException('Error al guardar el registro.');
            else                
                throw new ErrorException('Número de registro no existente.');
        }
    }
    
    public function offsetExists($offset) {
       return ( $offset < count($this) );
    }
    
    public function offsetUnset($offset) {       
       if ( !dbase_delete_record( $this->db, $offset+1) && $offset < count($this) )
           throw new ErrorException('Error al borrar el registro.');
       if ( dbase_pack($db) )
           throw new ErrorException('Error al borrar el registro.');  
       $this->rewind();
    }
    
    public function offsetGet($offset) {
        if ( $offset < count($this) )
            return new dbfRow( dbase_get_record( $this->db, $offset+1), $this->labels);
        else 
            throw new ErrorException('Registro no existente.');
    }
     
}
