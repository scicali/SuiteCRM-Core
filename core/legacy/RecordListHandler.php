<?php

namespace SuiteCRM\Core\Legacy;

use App\Entity\RecordList;
use App\Service\LegacyFilterMapper;
use App\Service\RecordListProviderInterface;
use App\Service\ModuleNameMapperInterface;
use BeanFactory;
use InvalidArgumentException;
use ListViewDataPort;
use SearchForm;
use SugarBean;
use Symfony\Component\Security\Core\Security;
use ViewList;

/**
 * Class RecordListHandler
 * @package SuiteCRM\Core\Legacy
 */
class RecordListHandler extends LegacyHandler implements RecordListProviderInterface
{
    public const HANDLER_KEY = 'list-view';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var LegacyFilterMapper
     */
    private $legacyFilterMapper;

    /**
     * @var Security
     */
    private $security;

    /**
     * SystemConfigHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param LegacyFilterMapper $legacyFilterMapper
     * @param Security $security
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        LegacyFilterMapper $legacyFilterMapper,
        Security $security
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->legacyFilterMapper = $legacyFilterMapper;
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string $moduleName
     * @param array $criteria
     * @param int $offset
     * @param int $limit
     * @param array $sort
     * @return RecordList
     */
    public function getList(
        string $moduleName,
        array $criteria = [],
        int $offset = -1,
        int $limit = -1,
        array $sort = []
    ): RecordList {
        $this->init();

        $listView = new RecordList();
        $moduleName = $this->validateModuleName($moduleName);
        $bean = $this->newBeanSafe($moduleName);

        $listViewData = $this->getData($bean, $criteria, $offset, $limit, $sort);

        if ($this->currentPageHasNoRecords($listViewData)) {
            $listViewData['pageData']['offsets']['current'] = 0;
            $listViewData = $this->getData($bean, $criteria, 0, $limit, $sort);
        }

        $listView->setId($moduleName);
        $listView->setMeta($this->getMeta($listViewData['pageData']));
        $listView->setRecords($this->getRecords($bean, $listViewData));

        $this->close();

        return $listView;
    }

    /**
     * @param array $listViewData
     * @return bool
     */
    protected function currentPageHasNoRecords(array $listViewData): bool
    {
        $totalRecords = (int)($listViewData['pageData']['offsets']['total'] ?? 0);
        $current = (int)($listViewData['pageData']['offsets']['current'] ?? 0);

        return $totalRecords && $current && $current >= $totalRecords;
    }

    /**
     * @param array $pageData
     * @return array
     */
    protected function getMeta(array $pageData): array
    {
        return [
            'offsets' => $pageData['offsets'],
            'ordering' => $pageData['ordering'],
        ];
    }

    /**
     * @param SugarBean $bean
     * @param array $fullViewData
     * @return array
     */
    protected function getRecords(SugarBean $bean, array $fullViewData): array
    {
        $processedData = [];
        foreach ($fullViewData['data'] as $key => $record) {
            $array = array_change_key_case($record);
            $id = $array['id'];
            unset($array['id']);

            $array = [
                'type' => $bean->object_name,
                'module' => $this->moduleNameMapper->toFrontEnd($bean->module_name),
                'id' => $id,
                'attributes' => $array,
                'relationships' => []
            ];

            $processedData[$key] = $array;
        }

        return $processedData;
    }

    /**
     * @param string $module
     *
     * @return SugarBean
     * @throws InvalidArgumentException When the module is invalid.
     */
    private function newBeanSafe($module): SugarBean
    {
        $bean = BeanFactory::newBean($module);

        if (!$bean instanceof SugarBean) {
            throw new InvalidArgumentException(sprintf('Module %s does not exist', $module));
        }

        return $bean;
    }

    /**
     * @param $moduleName
     * @return string
     */
    private function validateModuleName($moduleName): string
    {
        $moduleName = $this->moduleNameMapper->toLegacy($moduleName);

        if (!$this->moduleNameMapper->isValidModule($moduleName)) {
            throw new InvalidArgumentException('Invalid module name: ' . $moduleName);
        }

        return $moduleName;
    }

    /**
     * @param SugarBean $bean
     * @param array $criteria
     * @param int $offset
     * @param int $limit
     * @param array $sort
     * @return array
     */
    protected function getData(
        SugarBean $bean,
        array $criteria = [],
        int $offset = -1,
        int $limit = -1,
        array $sort = []
    ): array {
        $type = $criteria['type'] ?? 'advanced';

        $mapped = $this->legacyFilterMapper->mapFilters($criteria, $type);

        $baseCriteria = [
            'searchFormTab' => "${type}_search",
            'query' => 'true',
            'orderBy' => $this->legacyFilterMapper->getOrderBy($sort),
            'sortOrder' => $this->legacyFilterMapper->getSortOrder($sort)
        ];

        $legacyCriteria = array_merge($baseCriteria, $mapped);

        return $this->find($type, $bean, $offset, $limit, $legacyCriteria);
    }

    /**
     * @param string $type
     * @param SugarBean $bean
     * @param int $offset
     * @param int $limit
     * @param array $criteria
     * @return array
     */
    protected function find(string $type, SugarBean $bean, int $offset, int $limit, array $criteria = []): array
    {

        $legacyListView = $this->getLegacyListView($bean);
        $listViewDefs = $this->getListViewDefs($legacyListView);
        $searchForm = $this->getSearchForm($type, $bean, $listViewDefs, $criteria);
        $params = $this->getSortingParams($criteria);

        $where = $this->buildFilterClause($bean, $searchForm);

        $filter_fields = $legacyListView->lv->setupFilterFields([]);

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ListView/ListViewDataPort.php';
        $listViewData = new ListViewDataPort();

        return $listViewData->get($bean, $where, $offset, $limit, $filter_fields, $params);
    }

    /**
     * Get list view defs
     * @param ViewList $legacyListView
     * @return array
     */
    protected function getListViewDefs(ViewList $legacyListView): array
    {
        $listViewDefs = [];
        $metadataFile = $legacyListView->getMetaDataFile();

        require $metadataFile;

        return $listViewDefs;
    }

    /**
     * @param string $type
     * @param SugarBean $bean
     * @param array $listViewDefs
     * @param array $criteria
     * @return SearchForm
     */
    protected function getSearchForm(
        string $type,
        SugarBean $bean,
        array $listViewDefs,
        array $criteria = []
    ): SearchForm {

        /* @noinspection PhpIncludeInspection */
        require_once 'include/SearchForm/SearchForm2.php';
        $searchMetaData = SearchForm::retrieveSearchDefs($bean->module_name);
        $searchForm = new SearchForm($bean, $bean->module_name, 'index');
        $searchForm->setup(
            $searchMetaData['searchdefs'],
            $searchMetaData['searchFields'],
            'SearchFormGeneric.tpl',
            $type . '_search',
            $listViewDefs
        );

        $searchForm->populateFromArray($criteria);

        return $searchForm;
    }

    /**
     * @param SugarBean $bean
     * @param SearchForm $searchForm
     * @return string
     */
    protected function buildFilterClause(SugarBean $bean, SearchForm $searchForm): string
    {
        $where_clauses = $searchForm->generateSearchWhere(true, $bean->module_dir);

        $where = '';
        if (count($where_clauses) > 0) {
            $where = '(' . implode(' ) AND ( ', $where_clauses) . ')';
        }

        return $where;
    }

    /**
     * @param SugarBean $bean
     * @return ViewList
     */
    protected function getLegacyListView(SugarBean $bean): ViewList
    {
        $legacyListView = new ViewList();
        $legacyListView->bean = $bean;
        $legacyListView->module = $bean->module_name;
        $legacyListView->preDisplay();

        return $legacyListView;
    }

    /**
     * Get Legacy sorting parameters
     * @param array $criteria
     * @return array
     */
    protected function getSortingParams(array $criteria): array
    {
        $params = [];
        if (!empty($criteria['orderBy'])) {
            $params = [
                'orderBy' => strtoupper($criteria['orderBy']),
                'sortOrder' => $criteria['sortOrder'] ?? '',
                'skipOrderSave' => true,
                'overrideOrder' => true,
                'custom_order' => true
            ];
        }

        return $params;
    }
}