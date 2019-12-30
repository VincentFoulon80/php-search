<?php


namespace VFou\Search;


use Exception;
use VFou\Search\Query\QueryBuilder;
use VFou\Search\Query\QuerySegment;

class AdminPanel
{
    /**
     * @var Engine $engine
     */
    private $engine;

    /**
     * Admin constructor.
     * @param array $configuration
     * @throws Exception
     */
    public function __construct($configuration = [])
    {
        $this->engine = new Engine($configuration);
        $GLOBALS['vfou_admin'] = true;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if(strpos($uri, $_SERVER['SCRIPT_NAME']) == 0) $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        if(empty($uri)) $uri = '/';
        include('templates/header.php');
        switch($uri){
            case '/':
                $this->indexAction();
                break;
            case '/query':
                $this->QueryAction();
                break;
            case '/edit':
                $this->editAction();
                break;
            case '/types':
                $this->typesAction();
                break;
            case '/schemas':
                $this->schemasAction();
                break;
            case '/cache/clear':
                $this->engine->getIndex()->clearCache();
            default:
                header('location: '.$_SERVER['SCRIPT_NAME']);
                exit();
        }
        include('templates/footer.php');
    }

    /**
     * Route : /
     */
    private function indexAction(): void
    {
        $stats = $this->engine->getIndex()->getStats();
        include('templates/index.php');
    }

    /**
     * Route : /query
     * Methods : GET
     * Parameters :
     *     'q' : current query
     *     'limit' : maximum document count
     *     'offset' :  offset the results by this (for pagination purposes)
     *     'facets' : Display listed facets (comma separated)
     *     'connex' : Enable Connex Search
     * @throws Exception
     */
    private function QueryAction(): void
    {
        $q = $_GET['q'] ?? '';
        $sw = microtime(true);
        $segments = [];
        $isFacetSearching = false;
        foreach($_GET as $field=>$value){
            if(strpos($field,'facet-') === 0){
                $isFacetSearching = true;
                $facetField = substr($field, 6);
                $subSeg = [];
                foreach($value as $v){
                    $subSeg[] = QuerySegment::exactSearch($facetField, $v);
                }
                $segments[] = QuerySegment::or($subSeg);
            }
        }
        $query = new QueryBuilder(QuerySegment::search($q, QuerySegment::and($segments)));
        $query->setLimit($_GET['limit'] ?? 10);
        $query->setOffset($_GET['offset'] ?? 0);
        if($_GET['connex'] ?? false) $query->enableConnex();
        $facets = $_GET['facets'] ?? '';
        if(!empty($facets)){
            foreach(explode(',', $facets) as $facet){
                $query->addFacet($facet);
            }
        }
        if($isFacetSearching){
            $results = $this->engine->search($query);
        } else {
            $results = $this->engine->search($q, $query->getFilters());
        }
        $sw = (microtime(true) - $sw) * 1000;
        include('templates/results.php');
    }

    /**
     * Route : /edit
     * Methods : GET, POST
     * Parameters :
     *     'id' : document to find
     *     'prefill' : prefill the content with this
     *     'content' : JSON of a document's body
     *     'delete' : document id to delete
     * @throws Exception
     */
    private function editAction()
    {
        $errors = [];
        if(($_SERVER['REQUEST_METHOD'] ?? false) == 'POST'){
            if (isset($_POST['delete'])) {
                // deletes document
                $this->engine->delete($_POST['delete']);
                $this->engine->getIndex()->getDocument($_POST['delete']);
            } elseif(isset($_POST['prefill'])) {
                // we catch this here to prevent 'content' parameter to trigger
                // by the way, we disable the 'id' parameter
                $_GET['id'] = null;
            } else {
                // edit or create document
                $_POST['content'] = empty($_POST['content'] ?? '') ? '{}' : $_POST['content'];
                $content = json_decode($_POST['content'], true);
                if($content === null){
                    $errors[] = "ERROR : Invalid Json";
                } else {
                    // we parse DateTimes back to their Object
                    array_walk_recursive($content, function (&$elem) {
                        if (strpos($elem, '@@@DateTime:') === 0) {
                            $ts = substr($elem, 12);
                            $elem = new \DateTime();
                            $elem->setTimestamp($ts);
                        }
                    });
                    if (!isset($content['id'])) {
                        $errors[] = "ERROR : Document must have an 'id'";
                    }
                    if (!isset($content['type'])) {
                        $errors[] = "ERROR : Document must have a 'type'";
                    }
                }
                if (empty($errors)) {
                    // if everything's okay we create/update the document
                    try {
                        $this->engine->update($content);
                    } catch (\Throwable $exception) {
                        $errors[] = get_class($exception) . ": " . $exception->getMessage();
                    }
                }
                if (isset($content['id'])) $_GET['id'] = $content['id'];
            }
        }
        $id = $_GET['id'] ?? null;
        if(isset($id)){
            // fetch document and converts Datetime to a string representation
            $document = $this->engine->getIndex()->getDocument($id);
            array_walk_recursive($document, function(&$elem) {
                if(is_a($elem, \DateTime::class)) $elem = "@@@DateTime:".$elem->getTimestamp();
            });
        }
        include('templates/edit.php');
    }

    /**
     * Route : /types
     * Methods : GET
     * Parameters :
     *     'type' : currently selected type
     *     'text' : text to debug
     */
    private function typesAction()
    {
        $types = $this->engine->getIndex()->getTypes();
        if(!isset($_GET['type'])) $_GET['type'] = '_default';
        $debugTokens = [];
        if(!empty($_GET['text'])){
            $debugTokens = $this->engine->getIndex()->tokenizeQuery($_GET['text'] ?? '', $_GET['type']);
        }
        include('templates/types.php');
    }

    /**
     * Route : /schemas
     */
    private function schemasAction(): void
    {
        $schemas = $this->engine->getIndex()->getSchemas();
        include('templates/schemas.php');
    }
}
