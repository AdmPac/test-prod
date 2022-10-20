<style>
#settings{
    width:100%;
}

.arc{
    display: flex;
    flex-direction: column;
    border: 3px solid black;
    padding: 10px;
    text-align: left;
    width: 150px;
    line-height: 20px;
}

#arcS>.arc{
    margin:10px;
}

#arcS{
    display: flex;
    flex-wrap: wrap;
}

#patternAct{
    display:flex;
}

.ptrn{
    display: flex;
    flex-direction:column;
}
</style>

<?php
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

use Nebo\CashRegister\act as act;
use Nebo\CashRegister\settings as settings;


$request = HttpApplication::getInstance()->getContext()->getRequest();
\CModule::IncludeModule('nebo.cashregister');


// $settings = Option::get('nebo.cashregister','settingsStages');
$articleSettings = Settings::getSettingsArticle();
$article = settings::getArticle();
$gett = settings::get();
// Сохранение
if($request->isPost() && check_bitrix_sessid()) {
    
    $marker = false;

    $arcs = [
        'add' => $request['arcsAdd'],
        'del' => $request['arcsDel']
    ];
    
    $newPatternName = $request['newPatternName'];
    $newPatternData = $request['newPatternData'];
    
    file_put_contents(__DIR__.'/1.txt',print_r([$newPatternName, $newPatternData],1));

    if($newPatternName){
        $newPatternName = $newPatternName ?? [];
        settings::addPattern(
            [$newPatternName=>$newPatternData]
        );
    }

    if($arcs['add']){
        $marker = true;
    
        foreach($arcs['add'] as $name => $vArr){
            foreach($vArr as $k => $nameArc){
                $arcs['add'][$name][$k] = $article[$nameArc];
            }
        }    
    }
    
    if($arcs['del']){
        $marker = true;

        foreach($arcs['del'] as $name => $vArr){
            foreach($vArr as $k => $nameArc){
                $arcs['del'][$name][$k] = $article[$nameArc];
            }
        }
    }
    
    if($marker){
        foreach($gett as $name=>$vArr){
            $vArr = $vArr['UF_SETTINGS_ELEMENTS']['elements'];
            if($arcs['add'][$name]){
                $gett[$name]['UF_SETTINGS_ELEMENTS']['elements'] = array_merge($vArr,$arcs['add'][$name]);
            }

            $vArr = $gett[$name]['UF_SETTINGS_ELEMENTS']['elements'];
            if($arcs['del'][$name]){
                $gett[$name]['UF_SETTINGS_ELEMENTS']['elements'] = array_diff($vArr,$arcs['del'][$name]);
            }
        }

        $data = [];

        foreach($gett as $name=>$vArr){
            $data['code'] = $name;
            $vArr = $vArr['UF_SETTINGS_ELEMENTS']['elements'];
            $data['elements']['chapters']['UF_SETTINGS_ELEMENTS']['elements'] = $vArr;
            $result[] = settings::set($data);
        }
    }

    header("Refresh:0");
}

$tabs = [
    [
        'DIV'   => 'settingsActs',
        'TAB'   => "Настройки шаблонов актов",
        'TITLE' => "Составление актов из имеющихся статей",
    ],
    [
        'DIV'   => 'articles',
        'TAB'   => "Добавление шаблонов актов",
        'TITLE' => "Добавить шаблон",
    ]
];

$tabControl = new CAdminTabControl('tabControl', $tabs, true, true);
?>

<form method="POST">

    <?=bitrix_sessid_post();?>
    <? $tabControl->Begin();?>

    <? $tabControl->BeginNextTab(); ?>
        <?php
            foreach($articleSettings as $name => $vArr){
        ?>

        <tr>
            <td style="text-align:center;">
                <?php
                    echo "<div id='settings'>
                    <span style='text-align:left;'><h1>$name</h1></span>
                    <div id='arcS'>";   
                    foreach($article as $k => $arc){
                        if(in_array($k,$vArr)){
                            echo "<div class='arc' style='border:3px solid red;'>$k";
                            echo '<b>Удалить</b> <input type="checkbox" name="arcsDel['.$name.'][]" value="'.$k.'" style="margin-right:10px;">';
                        }
                        else{
                            echo "<div class='arc' style='border:3px solid green;'>$k";
                            echo '<b>Добавить</b> <input type="checkbox" name="arcsAdd['.$name.'][]" value="'.$k.'" style="margin-right:10px;">';
                        } 
                        echo '</div><br>';
                    }

                    echo '</div>';
                    echo '</div>';
                ?>
                <?='<hr>'?>
            </td>
        </tr>
        <?php
            } 
        ?>
        
        <? $tabControl->EndTab(); ?>
        <? $tabControl->BeginNextTab(); ?>
        <tr>
            <td>
                <div id="patternAct">
                    <?php
                        echo "<div id='settings'>";
                        echo '<input style="margin:10px" type="text" name="newPatternName" placeholder="Например, fotr">';
                        echo "<div id='arcS'>";

                        foreach($article as $k=>$arc){
                            echo "<div class='arc' style='border:3px solid green;'>$k";
                            echo '<b>Добавить</b> <input type="checkbox" name="newPatternData[]" value="'.$k.'" style="margin-right:10px;">';
                            echo '</div><br>';
                        }
                    ?>
                </div>
            </td>
        </tr>
        <? $tabControl->EndTab(); ?>

    <?$tabControl->Buttons(); ?>
        <input type="submit" name="Update" class="adm-btn-save">
    <?$tabControl->End();?>

</form>