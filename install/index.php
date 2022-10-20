<?php
use \Bitrix\Main\{
    Localization\Loc,
    Loader, 
    Type\DateTime,
    EventManager,
    Config\Option
};
use \Bitrix\Rest\APAuth\{PasswordTable, PermissionTable};

Loader::includeModule('nebo.dev');

use Nebo\Dev\EventHandles\Employment;

class nebo_cashregister extends CModule
{
    public $MODULE_ID = 'nebo.cashregister';
    public $MODULE_NAME;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;

    protected $nameSpaceValue;
    protected $subLocTitle;
    protected $optionClass;
    protected $definedContants;
    protected $moduleClass;
    protected $moduleClassPath;

    protected static $defaultSiteID;

    const USER_ID = 1;
    const SAVE_OPTIONS_WHEN_DELETED = true;

    /**
     * Опции, которые необходимо добавить в проект, сгруппированны по названиям, которые будут использоваться
     * в имени метода для их добавления. Опции описываются как ассоциативный массив, где "ключ" - центральная
     * часть имени метода, который будет вызван для добавления/удаления опций из каждой группы. Для того,
     * чтобы была инициализация опций в конкретной группе или их обработка перед удалением, необходимо
     * создать методы init<"Ключ">Options и remove<"Ключ">Options. В каждой группе опций, которые так же оформлены,
     * как ассоциативный массив, "ключ" - название константы, которая хранит название опции, эта константа должна
     * быть объявлена в файле include.php у модуля, под "значением" описываются настройки для инициализации каждого
     * элемента из группы опций. Итоговые данные опций после добавления будут сохранены в опциях модуля, каждый в
     * своей группе, для обращения к ним надо использовать класс Helpers\Options и методы по шаблону
     *     get<"Название группы опций">(<название конкретного элемента, необязательный параметр>)
     *
     * Если объявить в классе константу SAVE_OPTIONS_WHEN_DELETED со значением true, то все данные, добавленные
     * при установке модуля, при удалении модуля будут сохранены в системе и снова будут использоваться без
     * переустановки при новой установке модуля. Эта возможность автоматически унаследуется и для дочених модулей,
     * но эту константу можно переобъявив в дочерних модулях, изменив для тех модулей необходимость сохранения данных
     * при удалении модуля
     *
     * ВНИМАНИЕ. Не стоит в каждой группе данных объявлять настройки для каждого нового элемента группы
     * пусть и со своим уникальным именем константы, но с тем же самым значением константы, иначе после
     * установки модуль просто потеряет все кроме последнего установленные данные, что может привести к багу, а так же
     * после удаления модуля в системе останется мусор, т.е. информация, которую модуль установил, но не
     * смог удалить при своем удалении, так как ничего о ней не знал. Опции для данных в той же самой группе
     * должны храниться под "ключом", который явялется именем константы, значение которой уникально для
     * этой группы данных, то же "значение" под любым именем константы в той же самой группе данных
     * можно будет использовать в следующем модуле
     */
    const OPTIONS = [
        /**
         * Настройки для создания ВХОДЯЩИХ вебхуков. Каждый элемент это настройки для отдельного
         * вебхука.
         * "Ключ" - название константы, объявленной в файле include.php модуля, в которой сохранено
         * символьное имя вебхука, нужное только для хранения в настройках модуля.
         * "Значение" - сами настройки. Среди них обязательно нужно указать параметр "LANG_CODE",
         * в который записана языковая константа, хранящая название вебхука. Другие доступные
         * параметры (не обязательные)
         * "LANG_CODE_COMMENT" - языковая константа, хранящая описание вебхука
         * "SCOPES" - массив с кодами прав доступа, которые указываются в настройках ВХОДЯЩЕГО
         * вебхука
         */
        'WebHooks' => []
    ];

    /**
     * Описание обработчиков событий. Под "ключом" указывается название другого модуля, события которого
     * нужно обрабатывать, в "значении" указывается массив с навазниями классов этого модуля, которые
     * будут отвечать за обработку событий. Сам класс находится в папке lib модуля.
     * У названия класса не надо указывать пространство имен, кроме той части, что идет после
     * названий партнера и модуля. Для обработки конкретных событий эти классы должны иметь
     * статические и открытые методы с такими же названиями, что и события
     * Для создания обработчиков к конкретному highloadblock-у необходимо писать их названия
     * как <символьное имя highloadblock><название события>, например, для события OnAdd
     * у highloadblock с символьным именем Test такой обработчик должен называться TestOnAdd
     */
    const EVENTS_HANDLES = [
    ];

    /**
     * Пути к файлам и папкам, что лежат в папке install модуля,  на которые необходимо создать символьные ссылки
     * относительно папки local. Игнорируются файлы из папки www. Символная ссылка будет созданна на последнюю часть
     * указанного пути, по остальным частям будут созданны папки, если их нет. При удалении модуля сивмольная ссылка
     * удалится, а затем и все папки, в которые она входит, если в них больше ничего нет, и чьи названия указаны тут.
     * Если при установке выяснится, что символьная ссылка на последнюю часть пути уже существует, или на ее месте
     * находится папа, или одна из непоследних частей пути не является папкой, то произойдет ошибка
     * В ссылках можно использовать добавление подпути в виде имени одной из констант модуля, выделенной кваратными
     * скобками, это будет работать при установке файла в систему, может потребоваться, если нужно выделить файлы
     * модуля
     * [nebo_..._module_id] - пример, как надо использовать константы (многоточие это какое-то специальное слово модуля),
     * Так же по-умолчанию доступно [module_id], которое заменяется на идентификатор модуля
     */
    const FILE_LINKS = [
    ];

    /**
     * Запоминает и возвращает настоящий путь к текущему классу
     *
     * @return string
     */
    protected function getModuleClassPath()
    {
        if ($this->moduleClassPath) return $this->moduleClassPath;

        $this->moduleClass = new \ReflectionClass(get_called_class());
        // не надо заменять на __DIR__, так как могут быть дополнительные модули $this->moduleClassPath
        $this->moduleClassPath = rtrim(preg_replace('/[^\/\\\\]+$/', '', $this->moduleClass->getFileName()), '\//');
        return $this->moduleClassPath;
    }

    /**
     * Запоминает и возвращает код модуля, к которому относится текущий класс
     *
     * @return string
     */
    protected function getModuleId()
    {
        if ($this->MODULE_ID) return $this->MODULE_ID;

        return $this->MODULE_ID = basename(dirname($this->getModuleClassPath()));
    }

    /**
     * Запоминает и возвращает название именного пространства для классов из
     * библиотеки модуля
     *
     * @return string
     */
    protected function getNameSpaceValue()
    {
        if ($this->nameSpaceValue) return $this->nameSpaceValue;

        return $this->nameSpaceValue = preg_replace('/\.+/', '\\\\', ucwords($this->getModuleId(), '.'));
    }

    /**
     * Запоминает и возвращает название класса, используемого для установки и сохранения
     * опций текущего модуля
     *
     * @return string
     */
    protected function getOptionsClass()
    {
        if ($this->optionClass) return $this->optionClass;

        return $this->optionClass = $this->getNameSpaceValue() . '\\Helpers\\Options';
    }

    /**
     * Запоминает и возвращает кода сайта по-умолчанию
     *
     * @return string
     */
    protected static function getDefaultSiteID()
    {
        if (self::$defaultSiteID) return self::$defaultSiteID;

        return self::$defaultSiteID = CSite::GetDefSite();
    }

    /**
     * По переданному имени возвращает значение константы текущего класса с учетом того, что эта константа
     * точно была (пере)объявлена в этом классе модуля. Конечно, получить значение константы класса можно
     * и через <название класса>::<название константы>, но такая запись не учитывает для дочерних классов,
     * что константа не была переобъявлена, тогда она может хранить ненужные старые данные, из-за чего требуется
     * ее переобъявлять, иначе дочерние модули начнуть устанавливать то же, что и родительские, а переобъявление
     * требует дополнительного внимания к каждой константе и дополнительных строк в коде дочерних модулей
     *
     * @param string $constName - название константы
     * @return array
     */
    protected function getModuleConstantValue(string $constName)
    {
        $constant = $this->moduleClass->getReflectionConstant($constName);
        if (
            ($constant === false)
            || ($constant->getDeclaringClass()->getName() != get_called_class())
        ) return [];

        return $constant->getValue();
    }

    function __construct()
    {
        $this->getOptionsClass();
        Loc::loadMessages($this->getModuleClassPath() . '/' . basename(__FILE__));

        $this->subLocTitle = strtoupper(get_called_class()) . '_';
        $this->MODULE_NAME = Loc::getMessage($this->subLocTitle . 'MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage($this->subLocTitle . 'MODULE_DESCRIPTION');

        include  $this->moduleClassPath . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoUpdate()
    {
        global $APPLICATION;
        $this->initDefinedContants();


        try {
            if (!class_exists($this->optionClass))
                throw new Exception(Loc::getMessage('ERROR_NO_OPTION_CLASS', ['#CLASS#' => $this->optionClass]));
            Employment::setBussy();
            $this->checkAndRunModuleEvent('onBeforeModuleUpdatingMethods');

            $this->updateEventHandles();

            $this->optionClass::setConstants(array_keys($this->definedContants));
            $this->optionClass::setInstallShortData([
                'INSTALL_DATE' => date('Y-m-d H:i:s'),
                'VERSION' => $this->MODULE_VERSION,
                'VERSION_DATE' => $this->MODULE_VERSION_DATE,
            ]);
            $this->optionClass::save();
            $this->checkAndRunModuleEvent('onAfterModuleUpdatingMethods');
            Employment::setFree();
        } catch (Exception $error) {
            $_SESSION['MODULE_ERROR'] = $error->getMessage();
            Employment::setFree();
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage($this->subLocTitle . 'MODULE_NOT_INSTALLED'),
                $this->moduleClassPath . '/error.php'
            );
        }
    }

    public function updateEventHandles()
    {
        $this->removeEventHandles();
        $this->initEventHandles();
        $this->removeFileLinks();
        $this->initFileLinks();
    }

    /**
     * Возвращает обработынный список констант из $definedContants, в результате
     * будет <ключ> - заключенное в [] и приведенное к нижнему регистру имя ключа
     *
     * @param array $definedContants - массив констант, где
     * <ключ> - название константы, а <значение> - значение константы
     *
     * @return array
     */
    protected static function getPartTemplateByData(array $definedContants)
    {
        $resultDefinedContants = [];
        foreach ($definedContants as $code => $value) {
            if (!preg_match('/^\w+$/', $code)) continue;

            $resultDefinedContants['[' . strtolower($code) . ']'] = $value;
        }
        return $resultDefinedContants;
    }

    /**
     * Создание ВХОДЯЩИХ вебхуков
     *
     * @param string $constName - название константы
     * @param array $optionValue - значение опции
     * @return mixed
     */
    public function initWebHooksOptions(string $constName, array $optionValue)
    {
        Loader::includeModule('rest');

        $resultDefinedContants = ['[module_id]' => basename(dirname($this->moduleClassPath))]
            + self::getPartTemplateByData($this->definedContants);

        $data = [
            'TITLE' => empty($optionValue['LANG_CODE']) ? '' : Loc::getMessage($optionValue['LANG_CODE']),
            'COMMENT' => empty($optionValue['LANG_CODE_COMMENT']) ? '' : Loc::getMessage($optionValue['LANG_CODE_COMMENT']),
        ];
        if (empty($data['TITLE'])) throw new Exception(Loc::getMessage('ERROR_WEB_HOOD_TITLE', ['#WEBHOOK#' => $constName]));
        $data = array_map(
                function($value) use($resultDefinedContants) {
                    return strtr($value, $resultDefinedContants);
                },
                $data
            ) + [
                'DATE_CREATE' => new DateTime(),
                'USER_ID' => self::USER_ID,
                'PASSWORD' => PasswordTable::generatePassword()
            ];
        $result = PasswordTable::add($data);
        if (!$result->isSuccess()) throw new Exception(Loc::getMessage('ERROR_WEB_HOOD_CREATION', ['#WEBHOOK#' => $constName]));

        $webHookId = $result->getId();
        if (!empty($optionValue['SCOPES'])) {
            foreach ($optionValue['SCOPES'] as $scope) {
                PermissionTable::add([
                    'PASSWORD_ID' => $webHookId,
                    'PERM' => $scope,
                ]);
            }
        }
        return $webHookId;
    }

    /**
     * Создание всех опций
     *
     * @return  void
     */
    public function initOptions()
    {
        $savedData = [];
        $saveDataWhenDeleted = constant(get_called_class() . '::SAVE_OPTIONS_WHEN_DELETED') === true;
        if ($saveDataWhenDeleted)
            $savedData = json_decode(Option::get('main', 'saved.' . $this->MODULE_ID, false, \CSite::GetDefSite()), true)
                ?: [];

        foreach ($this->getModuleConstantValue('OPTIONS') as $methodNameBody => $optionList) {
            $methodName = 'init' . $methodNameBody . 'Options';
            if (!method_exists($this, $methodName)) continue;

            foreach ($optionList as $constName => $optionValue) {
                if (!defined($constName)) return;

                $constValue = constant($constName);
                $value = empty($savedData[$methodNameBody][$constValue])
                    ? $this->$methodName($constName, $optionValue)
                    : $savedData[$methodNameBody][$constValue];
                if (!isset($value)) continue;
                $optionMethod = 'add' . $methodNameBody;
                $this->optionClass::$optionMethod($constValue, $value);
            }
        }
    }

    /**
     * Регистрация обработчиков событий
     *
     * @return void
     */
    public function initEventHandles()
    {
        $eventManager = EventManager::getInstance();
        $eventsHandles = [];
        foreach ($this->getModuleConstantValue('EVENTS_HANDLES') as $moduleName => $classNames) {
            foreach ($classNames as $className) {
                $classNameValue = $this->nameSpaceValue . '\\' . $className;
                if (!class_exists($classNameValue)) continue;

                $registerModuleName = $moduleName == 'highloadblock' ? '' : $moduleName;
                $reflectionClass = new ReflectionClass($classNameValue);
                foreach ($reflectionClass->getMethods() as $method) {
                    if (!$method->isPublic() || !$method->isStatic()) continue;

                    $eventName = $method->getName();
                    $eventsHandles[$moduleName][$eventName][] = $className;
                    $eventManager->registerEventHandler(
                        $registerModuleName, $eventName, $this->MODULE_ID, $classNameValue, $eventName
                    );
                }
            }
        }
        $this->optionClass::setEventsHandles($eventsHandles);
    }

    /**
     * Функция-генератор, по списку переданных файлов делает предобработку названия каждого файла
     * и возвращает  обработанное название файла, рарзделенный на части путь к файлу и его длину.
     * Благодаря второму параметру exclude, в котором указываются пути для исключений, можно отбросить
     * все переданные в списке файлы, путь к которым введен в эти пути для исключения
     *
     * @param array $files - список файлов
     * @param array $exclude - пути для исключения файлов
     * @param array $definedContants - массив с константами, которые надо заменить в именах списка файлов.
     * Сами константы в файлах должны быть указаны как
     * [<имя константы только из букв латинского алфавита, подчеркивания и цифр>]
     * По-умолчанию, обрабатывается и константа [module_id] с заменой на идентификатор модуля
     */
    protected function getFileParts(array $files, array $exclude = [], array $definedContants = [])
    {
        $resultDefinedContants = ['[module_id]' => basename(dirname($this->moduleClassPath))]
            + self::getPartTemplateByData($definedContants);
        $excludeFiles = array_map(
            function($eFile) use($resultDefinedContants) {
                $parts = preg_split('/[\\\\\/]+/', strtr(strtolower(trim($eFile , '\\/')), $resultDefinedContants));
                return ['count' => count($parts), 'parts' => $parts, 'path' => implode('/', $parts)];
            }, $exclude
        );
        foreach ($files as $moduleFile) {
            $fileTarget =  strtolower(preg_replace('/[\\\\\/]+/', '/', trim($moduleFile , '\\/')));
            $resultFileTarget = strtr($fileTarget, $resultDefinedContants);
            $fileParts = explode('/', $resultFileTarget);
            $filePartsSize = count($fileParts);
            if (
            count(array_filter(
                $excludeFiles,
                function($ePath) use($resultFileTarget, $fileParts, $filePartsSize) {
                    if ($ePath['count'] <= $filePartsSize) {
                        return implode('/', array_slice($fileParts, 0, $ePath['count'])) == $ePath['path'];

                    } else {
                        return $resultFileTarget == implode('/', array_slice($ePath['parts'], 0, $filePartsSize));
                    }
                }
            ))
            ) continue;
            yield [
                'target' => preg_replace('/\/+/', '/', preg_replace('/\[\w+\]/', '', $fileTarget)),
                'parts' => $fileParts,
                'count' => $filePartsSize
            ];
        }
    }

    /**
     * Создание символьных ссылок в папке local
     *
     * @return void
     */
    public function initFileLinks()
    {
        $localLinks = [];
        $fromPath = $this->moduleClassPath . '/';
        foreach ($this->getFileParts($this->getModuleConstantValue('FILE_LINKS'), ['www'], $this->definedContants) as $moduleFile) {
            $targetFromPath = $fromPath . $moduleFile['target'];
            if (!file_exists($targetFromPath)) continue;

            $lastPartNum = $moduleFile['count'] - 1;
            $subResult = '';
            foreach ($moduleFile['parts'] as $pathNum => $subPath) {
                $subResult .= '/' . $subPath;
                $result = $_SERVER['DOCUMENT_ROOT'] . '/local' . $subResult;
                if (!file_exists($result)) {
                    if ($lastPartNum > $pathNum) {
                        mkdir($result);

                    } else {
                        symlink($targetFromPath, $result);
                        $localLinks[$moduleFile['target']] = ['result' => $subResult];
                    }

                } elseif (!is_dir($result) || is_link($result) || ($lastPartNum == $pathNum)) {
                    $this->optionClass::setLocalLinks($localLinks);
                    throw new Exception(Loc::getMessage('ERROR_LINK_CREATING', ['LINK' => $moduleFile['target']]));
                }
            }
        }
        $this->optionClass::setLocalLinks($localLinks);
    }

    /**
     * Подключает модуль и сохраняет созданные им константы
     *
     * @return void
     */
    protected function initDefinedContants()
    {
        /**
         * array_keys нужен, так как в array_filter функция isset дает
         * лишнии результаты
         */
        $this->definedContants = array_keys(get_defined_constants());

        Loader::IncludeModule($this->MODULE_ID);
        $this->definedContants = array_filter(
            get_defined_constants(),
            function($key) {
                return !in_array($key, $this->definedContants);
            }, ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Выполняется основные операции по установке модуля
     *
     * @return void
     */
    protected function runInstallMethods()
    {
        $this->initOptions();
        $this->initEventHandles();
        $this->initFileLinks();
    }

    /**
     * Устанавливает модуль, но сначала проверяет не является ли он
     * дочерним, а, если это так, то при условии, что родительские модули
     * не установлены, сначала устанавливает их
     *
     * @return void
     */
    protected function initFullInstallation()
    {
        set_time_limit(0);
        $parentClassName = get_parent_class(get_called_class());
        if (($parentClassName != 'CModule') && !(new $parentClassName())->IsInstalled())
            (new $parentClassName())->DoInstall(false);

        RegisterModule($this->MODULE_ID);
    }

    /**
     * Проверяет у модуля наличие класса Employment в своем подпространстве имен EventHandles,
     * а так же наличие у него метода, название которого передано в параметре $methodName.
     * В случае успеха вызывает метод у своего Employment
     *
     * @param string $methodName - название метода, который должен выступать как обработчик события
     * @return void
     */
    protected function checkAndRunModuleEvent(string $methodName)
    {
        $moduleEmployment = $this->nameSpaceValue . '\\EventHandles\\Employment';
        if (!class_exists($moduleEmployment) || !method_exists($moduleEmployment, $methodName))
            return;

        $moduleEmployment::$methodName();
    }

    /**
     * Функция, вызываемая при установке модуля
     *
     * @param bool $stopAfterInstall - указывает модулю остановить после
     * своей установки весь процесс установки
     *
     * @return void
     */
    public function DoInstall(bool $stopAfterInstall = true)
    {
        global $APPLICATION;
        $this->initFullInstallation();
        $this->initDefinedContants();

        try {
            if (!class_exists($this->optionClass))
                throw new Exception(Loc::getMessage('ERROR_NO_OPTION_CLASS', ['#CLASS#' => $this->optionClass]));
            Employment::setBussy();
            $this->checkAndRunModuleEvent('onBeforeModuleInstallationMethods');
            $this->runInstallMethods();
            $this->optionClass::setConstants(array_keys($this->definedContants));
            $this->optionClass::setInstallShortData([
                'INSTALL_DATE' => date('Y-m-d H:i:s'),
                'VERSION' => $this->MODULE_VERSION,
                'VERSION_DATE' => $this->MODULE_VERSION_DATE,
            ]);
            $this->optionClass::save();
            $this->checkAndRunModuleEvent('onAfterModuleInstallationMethods');
            Employment::setFree();
            if ($stopAfterInstall)
                $APPLICATION->IncludeAdminFile(
                    Loc::getMessage($this->subLocTitle . 'MODULE_WAS_INSTALLED'),
                    $this->moduleClassPath . '/step1.php'
                );

        } catch (Exception $error) {
            $this->removeAll();
            $_SESSION['MODULE_ERROR'] = $error->getMessage();
            Employment::setFree();
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage($this->subLocTitle . 'MODULE_NOT_INSTALLED'),
                $this->moduleClassPath . '/error.php'
            );
        }
    }

    /**
     * Удаление ВХОДЯЩИХ вебхуков
     *
     * @param string $constName - название константы
     * @param array $optionValue - значение опции
     * @return mixed
     */
    public function removeWebHooksOptions(string $constName, array $optionValue)
    {
        $webHookId = intval($this->optionClass::getWebHooks(constant($constName)));
        if (!$webHookId) return;

        PermissionTable::deleteByPasswordId($webHookId);
        PasswordTable::Delete($webHookId);
    }

    /**
     * Удаление всех созданных модулем данных согласно прописанным настройкам в
     * OPTIONS
     *
     * @return void
     */
    public function removeOptions()
    {
        $saveDataWhenDeleted = constant(get_called_class() . '::SAVE_OPTIONS_WHEN_DELETED') === true;
        $savedData = [];
        foreach (array_reverse($this->getModuleConstantValue('OPTIONS')) as $methodNameBody => $optionList) {
            $methodName = $saveDataWhenDeleted && !in_array(strtolower($methodNameBody), ['agents'])
                ? 'get' . $methodNameBody
                : 'remove' . $methodNameBody . 'Options';

            foreach ($optionList as $constName => $optionValue) {
                if (!defined($constName)) continue;

                if ($saveDataWhenDeleted) {
                    $constValue = constant($constName);
                    $data = $this->optionClass::$methodName($constValue);
                    if (empty($data)) continue;
                    $savedData[$methodNameBody][$constValue] = $data;

                } elseif (method_exists($this, $methodName)) {
                    $this->$methodName($constName, $optionValue);
                }
            }
        }
        if (!empty($savedData))
            Option::set('main', 'saved.' . $this->MODULE_ID, json_encode($savedData));
    }

    /**
     * Удаление всех зарегистрированных модулем обработчиков событий
     *
     * @return void
     */
    public function removeEventHandles()
    {
        $eventManager = EventManager::getInstance();
        foreach ($this->optionClass::getEventsHandles() as $moduleName => $eventList) {
            foreach (array_keys($eventList) as $eventName) {
                foreach (
                    $eventManager->findEventHandlers(
                        strtoupper($moduleName),
                        strtoupper($eventName),
                        ['TO_MODULE_ID' => $this->MODULE_ID]
                    ) as $handle) {

                    $eventManager->unRegisterEventHandler(
                        $moduleName, $eventName, $this->MODULE_ID, $handle['TO_CLASS'], $handle['TO_METHOD']
                    );
                }
            }
        }
    }

    /**
     * Удаляет файла, а затем папку, в которой он лежит, если в ней больше ничего нет,
     * после чего по такому же принципу удаляет все родительские папки до папки _local
     *
     * @param string $fileTarget - относительный путь к файлу
     * @param string $where - начальный путь к файлу
     * @return void
     */
    protected static function deleteEmptyPath(string $fileTarget, string $where)
    {
        $result = $where . $fileTarget;
        if (is_link($result) || !is_dir($result)) {
            @unlink($result) || rmdir($result);

        } else {
            rmdir($result);
        }

        $toDelete = true;
        while ($toDelete && ($fileTarget = preg_replace('/\/?[^\/]+$/', '', $fileTarget))) {
            $result = $where . $fileTarget;
            $dUnit = opendir($result);
            while ($fUnit = readdir($dUnit)) {
                if (($fUnit == '.') || ($fUnit == '..')) continue;

                $toDelete = false;
                break;
            }
            closedir($dUnit);
            if ($toDelete) rmdir($result);
        }
    }

    /**
     * Удаляет файлы, которые были созданы модулем как символьная ссылка на такой же файл в модуле.
     * Вызывает callback-функцию, если она была передана, с обработанным названием файла
     *
     * @param array $files - список файлов из папки модуля с установочным файлом index.php
     * @param string $from - относительный путь к подпапке из папки модуля с установочным файлом index.php, где
     * должны лежать указанные в $files файлы
     * @param string $where - путь относительно корня сайта, где будут проверяться и удаляться файлы
     * @param array $definedContants - массив с константами, которые надо заменить в именах списка файлов.
     * Сами константы в файлах должны быть указаны как
     * [<имя константы только из букв латинского алфавита, подчеркивания и цифр>]
     * По-умолчанию, обрабатывается и константа [module_id] с заменой на идентификатор модуля
     *
     * @param $callback - необязательный обработчик для каждого файла модуля. Передаются, если будет указан,
     * два параметра - имя файла из модуля и параметры установленного файла в виде массива, где
     *     <result> - имя файла, который был установлен в системе
     *     <old> - имя файла, которые было ранее до установленного, а теперь переименовано
     * @return void
     */
    protected function removeFiles(array $files, string $from, string $where, array $definedContants, $callback = null)
    {
        $resultDefinedContants = ['[module_id]' => basename(dirname($this->moduleClassPath))]
            + self::getPartTemplateByData($definedContants);
        $fromPath = $this->moduleClassPath  . (trim($from) ? '/' : '') . trim($from) . '/';
        $wherePath = $_SERVER['DOCUMENT_ROOT'] . (trim($where) ? '/' : '') . trim($where) . '/';
        foreach ($files as $moduleFile => $moduleResult) {
            if (file_exists($fromPath . $moduleFile) && is_link($wherePath . $moduleResult['result']))
                self::deleteEmptyPath($moduleResult['result'], $wherePath);

            if (is_callable($callback)) $callback($moduleFile, $moduleResult);
        }
    }

    /**
     * Удаление всех созданных модулем символьных ссылок
     *
     * @return void
     */
    public function removeFileLinks()
    {
        $this->removeFiles($this->optionClass::getLocalLinks() ?? [], '', 'local', $this->definedContants ?? []);
    }

    /**
     * Выполняется основные операции по удалению модуля
     *
     * @return void
     */
    protected function runRemoveMethods()
    {
        $this->removeFileLinks();
        $this->removeEventHandles();
        $this->removeOptions();
    }

    /**
     * Основной метод, очищающий систему от данных, созданных им
     * при установке
     *
     * @return void
     */
    public function removeAll()
    {
        if (class_exists($this->optionClass)) $this->runRemoveMethods();
        UnRegisterModule($this->MODULE_ID); // удаляем модуль
    }

    /**
     * Проверяет, есть ли у модуля дочернии модули среди установленных.
     * Если такие есть, то сначала удаляются они
     *
     * @return void
     */
    protected function killAllChildren()
    {
        $className = get_called_class();
        $modules = self::GetList();
        while ($module = $modules->Fetch()) {
            $childClass = str_replace('.', '_', $module['ID']);
            if (!class_exists($childClass) || (get_parent_class($childClass) != $className))
                continue;

            (new $childClass())->DoUninstall(false);
        }
    }

    /**
     * Функция, вызываемая при удалении модуля
     *
     * @param bool $stopAfterDeath - указывает модулю остановить после
     * своего удаления весь процесс удаления
     *
     * @return void
     */
    public function DoUninstall(bool $stopAfterDeath = true)
    {
        global $APPLICATION;
        $this->killAllChildren();
        Loader::IncludeModule($this->MODULE_ID);
        Employment::setBussy();
        $this->checkAndRunModuleEvent('onBeforeModuleRemovingMethods');
        $this->definedContants = array_fill_keys($this->optionClass::getConstants() ?? [], '');
        array_walk($this->definedContants, function(&$value, $key) { $value = constant($key); });
        $this->removeAll();
        Option::delete($this->MODULE_ID);
        $this->checkAndRunModuleEvent('onAfterModuleRemovingMethods');
        Employment::setFree();
        if ($stopAfterDeath)
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage($this->subLocTitle . 'MODULE_WAS_DELETED'),
                $this->moduleClassPath . '/unstep1.php'
            );
    }

}
