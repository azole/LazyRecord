<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;
use RuntimeException;
use IteratorAggregate;
use LazyRecord\ClassUtils;
use LazyRecord\ConfigLoader;
use LazyRecord\ServiceContainer;
use LazyRecord\Schema\SchemaUtils;
use CLIFramework\Logger;
use Traversable;

/**
 * Find schema classes from files (or from current runtime)
 *
 * 1. Find SchemaDeclare-based schema class files.
 * 2. Find model-based schema, pass dynamic schema class 
 */
class SchemaFinder implements IteratorAggregate
{
    public $paths = array();

    protected $logger;

    public function __construct(array $paths = array(), Logger $logger = null) {
        $this->paths = $paths;
        $c = ServiceContainer::getInstance();
        $this->logger = $logger ?: $c['logger'];
    }

    public function in($path)
    {
        $this->paths[] = $path;
    }

    public function setPaths(array $paths) {
        $this->paths = $paths;
    }

    // DEPRECATED
    public function loadFiles() { 
        return $this->find(); 
    }

    public function find()
    {
        if (empty($this->paths)) {
            return;
        }

        $this->logger->debug('Finding schemas in (' . join(', ',$this->paths) . ')');
        foreach ($this->paths as $path) {
            $this->logger->debug('Finding schemas in ' . $path);
            if (is_file($path)) {
                $this->logger->debug('Loading schema: ' . $path);
                require_once $path;
            } else if (is_dir($path)) {
                $rii   = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path,
                        RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($rii as $fi) {
                    if (substr($fi->getFilename(), -10) == "Schema.php") {
                        $this->logger->debug('Loading schema: ' . $fi->getPathname());
                        require_once $fi->getPathname();
                    }
                }
            }
        }
    }


    /**
     * Returns schema objects
     *
     * @return array Schema objects
     */
    public function getSchemas()
    {
        return SchemaUtils::expandSchemaClasses(
            ClassUtils::get_declared_schema_classes()
        );
    }

    public function getIterator()
    {
        return $this->getSchemas();
    }
}

