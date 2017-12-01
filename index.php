<?php
    $dbLocation = 'mysql:dbname=service_map;host=localhost';
    $dbUser = 'root';
    $dbPass = '';
    $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

    $sql = 'SELECT * FROM tbl_entity_line';

    $query = $db->prepare($sql);
    $query->execute();
    $line_fetch = $query->fetchAll(PDO::FETCH_ASSOC);

    $sql = 'SELECT * FROM tbl_entity_stop ORDER BY line_id, line_position';
    $query = $db->prepare($sql);
    $query->execute();
    $stop_fetch = $query->fetchAll(PDO::FETCH_ASSOC);

    $canvas_size = 1000;    //canvas width in px
    $canvas_ratio = 1.25;   //canvas width/height
    $curve_size = 0.025;
    $stroke_width = 0.004;
    $circle_radius = 0.006;

    $container_font_size = 1.25;
    $line_name_font_size = 0.8;
    $stop_name_font_size = 0.8;
    $junction_stop_name_font_size = 1;

    function get_path_point($path_value)
    {
        return round($path_value*$GLOBALS['canvas_size'],2);
    }

    $line_set = [];
    foreach ($line_fetch as $line_fetch_row) {
        $line_row = [
            'id'=>$line_fetch_row['id'],
            'name'=>$line_fetch_row['name'],
            'alternate_name'=>$line_fetch_row['alternate_name'],
            'color'=>json_decode($line_fetch_row['color'],true),
            'path'=>json_decode($line_fetch_row['path'],true),
            'content'=>$line_fetch_row['content']
        ];
        $path_length_set = [];
        $path_d = '';
        $previous_endpoint = [0,0];
        $path_endpoint_count = count($line_row['path']);
        foreach ($line_row['path'] as $path_endpoint_index=>$path_endpoint)
        {
            if ($path_endpoint_index == 0)
            {
                $path_d = 'M'.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]);
                continue;
            }
            $path_length = sqrt(pow($path_endpoint[0]-$line_row['path'][$path_endpoint_index-1][0],2)+pow($path_endpoint[1]-$line_row['path'][$path_endpoint_index-1][1],2));
            $path_length_set[] = $path_length;
            if ($path_length > $curve_size*2)
            {
                if ($path_endpoint_index > 1)
                {
                    $path_d .= get_path_point($curve_size/$path_length*$path_endpoint[0]+(1-$curve_size/$path_length)*$line_row['path'][$path_endpoint_index-1][0]).' '.get_path_point($curve_size/$path_length*$path_endpoint[1]+(1-$curve_size/$path_length)*$line_row['path'][$path_endpoint_index-1][1]);
                }
                if ($path_endpoint_index < $path_endpoint_count-1)
                {
                    $path_d .= ' L'.get_path_point($curve_size/$path_length*$line_row['path'][$path_endpoint_index-1][0]+(1-$curve_size/$path_length)*$path_endpoint[0]).' '.get_path_point($curve_size/$path_length*$line_row['path'][$path_endpoint_index-1][1]+(1-$curve_size/$path_length)*$path_endpoint[1]);
                    $path_d .= ' C'.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]).', '.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]).', ';
                }
                else
                {
                    $path_d .= ' L'.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]);
                }
            }
            else
            {
                if ($path_endpoint_index > 1)
                {
                    $path_d .= get_path_point(0.5*$line_row['path'][$path_endpoint_index-1][0]+0.5*$path_endpoint[0]).' '.get_path_point(0.5*$line_row['path'][$path_endpoint_index-1][1]+0.5*$path_endpoint[1]);
                }
                if ($path_endpoint_index < $path_endpoint_count-1)
                {
                    $path_d .= ' C'.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]).', '.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]).', ';
                }
                else
                {
                    $path_d .= ' L'.get_path_point($path_endpoint[0]).' '.get_path_point($path_endpoint[1]);
                }
            }
        }
        $line_row['d'] = $path_d;
        $line_set[$line_fetch_row['id']] = $line_row;
    }

    $stop_set = [];
    $junction_set = [];
    foreach ($stop_fetch as $stop_fetch_row)
    {
        $stop_row = [
            'id'=>$stop_fetch_row['id'],
            'name'=>$stop_fetch_row['name'],
            'description'=>$stop_fetch_row['description'],
            'line_id'=>$stop_fetch_row['line_id'],
            'junction_id'=>$stop_fetch_row['junction_id'],
            'x'=>$stop_fetch_row['point_x'],
            'y'=>$stop_fetch_row['point_y'],
            'cx'=>get_path_point($stop_fetch_row['point_x']),
            'cy'=>get_path_point($stop_fetch_row['point_y']),
            'text_location'=>''
        ];

        if ($stop_fetch_row['text_x'] != 0)
        {
            $stop_row['text_location'] .= 'margin-left:0;';
        }
        else
        {
            $stop_row['text_location'] .= 'text-align:center;';
        }
        if ($stop_fetch_row['text_x'] >= 0)
        {
            $stop_row['text_location'] .= 'left:'.(($stop_fetch_row['point_x']+$stop_fetch_row['text_x'])*100).'%;';
        }
        else
        {
            $stop_row['text_location'] .= 'right:'.((1-$stop_fetch_row['point_x']-$stop_fetch_row['text_x'])*100).'%;text-align:right;';
        }

        if ($stop_fetch_row['text_y'] != 0)
        {
            $stop_row['text_location'] .= 'margin-top:0;';
        }
        else
        {
            $stop_row['text_location'] .= 'vertical-align:middle;';
        }
        if ($stop_fetch_row['text_y'] >= 0)
        {
            $stop_row['text_location'] .= 'top:'.(($stop_fetch_row['point_y']+$stop_fetch_row['text_y'])*100*$canvas_ratio).'%;';
        }
        else
        {
            $stop_row['text_location'] .= 'bottom:'.((1/$canvas_ratio-$stop_fetch_row['point_y']-$stop_fetch_row['text_y'])*100*$canvas_ratio).'%;vertical-align:bottom;';
        }

        $stop_set[$stop_fetch_row['id']] = $stop_row;
        $line_set[$stop_fetch_row['line_id']]['stop'][$stop_fetch_row['line_position']] = $stop_row;

        if ($stop_fetch_row['junction_id'] > 0)
        {
            $junction_set[$stop_fetch_row['junction_id']]['stop'][$stop_fetch_row['junction_position']] = $stop_row;
        }
    }
    foreach($junction_set as $junction_row_index=>&$junction_row)
    {
        ksort($junction_row['stop']);
        $junction_row['name'] = $junction_row['stop'][0]['name'];
        $junction_row['description'] = $junction_row['stop'][0]['description'];
        $junction_row['junction_id'] = $junction_row['stop'][0]['junction_id'];
        $junction_row['text_location'] = $junction_row['stop'][0]['text_location'];
        $junction_row['cx'] = $junction_row['stop'][0]['cx'];
        $junction_row['cy'] = $junction_row['stop'][0]['cy'];
        $junction_row['line_id'] = [];

        $center_point_set = [];
        foreach($junction_row['stop'] as $junction_stop_index=>$junction_stop)
        {
            $junction_row['line_id'][] = $junction_stop['line_id'];
            $center_point = ['x'=>$junction_stop['x'],'y'=>$junction_stop['y']];
            if ($junction_stop_index > 0)
            {
                if ($center_point['x'] == end($center_point_set)['x'] AND $center_point['y'] == end($center_point_set)['y'])
                {
                    continue;
                }
                if (count($center_point_set) > 2)
                {
                    // If previous point on the path from second but last point to new point, then remove the last point
                    if (($center_point['y'] == end($center_point_set)['y'] AND $center_point['y'] == $center_point_set[lenth($center_point_set)-2]['y']) OR ($center_point['x']-$center_point_set[lenth($center_point_set)-2]['x'])/($center_point['y']-$center_point_set[lenth($center_point_set)-2]['y']) == ($center_point['x']-end($center_point_set)['x'])/($center_point['y']-end($center_point_set)['y']))
                    {
                        array_pop($center_point_set);
                    }
                }
            }
            $center_point_set[] = $center_point;
        }
        if (count($center_point_set) > 1)
        {
            $junction_row['d'] = '';
            $path_d_end = [];
            $start_point = [];
            $end_point = [];
            foreach($center_point_set as $junction_center_index=>$junction_center)
            {
                if ($junction_center_index > 0 AND $junction_center_index < count($center_point_set)-1)
                {

                }
                else
                {
                    if ($junction_center_index == 0)
                    {
                        $ratio = $circle_radius/(sqrt(pow($center_point_set[$junction_center_index+1]['x']-$junction_center['x'],2)+pow($center_point_set[$junction_center_index+1]['y']-$junction_center['y'],2)));
                        $end_point = ['x'=>($center_point_set[$junction_center_index+1]['y']-$junction_center['y'])*$ratio+$junction_center['x'],'y'=>-1*($center_point_set[$junction_center_index+1]['x']-$junction_center['x'])*$ratio+$junction_center['y']];
                        $start_point = ['x'=>-1*($center_point_set[$junction_center_index+1]['y']-$junction_center['y'])*$ratio+$junction_center['x'],'y'=>($center_point_set[$junction_center_index+1]['x']-$junction_center['x'])*$ratio+$junction_center['y']];
                        $junction_row['d'] .= 'M'.get_path_point($end_point['x']).' '.get_path_point($end_point['y']).' A'.get_path_point($circle_radius).' '.get_path_point($circle_radius).', 0, 0, 0, '.get_path_point($start_point['x']).' '.get_path_point($start_point['y']);
                        array_unshift($path_d_end,'Z');
                    }
                    else
                    {
                        $ratio = $circle_radius/(sqrt(pow($center_point_set[$junction_center_index-1]['x']-$junction_center['x'],2)+pow($center_point_set[$junction_center_index-1]['y']-$junction_center['y'],2)));
                        $start_point = ['x'=>-1*($junction_center['y']-$center_point_set[$junction_center_index-1]['y'])*$ratio+$junction_center['x'],'y'=>($junction_center['x']-$center_point_set[$junction_center_index-1]['x'])*$ratio+$junction_center['y']];
                        $end_point = ['x'=>($junction_center['y']-$center_point_set[$junction_center_index-1]['y'])*$ratio+$junction_center['x'],'y'=>-1*($junction_center['x']-$center_point_set[$junction_center_index-1]['x'])*$ratio+$junction_center['y']];
                        $junction_row['d'] .= ' L'.get_path_point($start_point['x']).' '.get_path_point($start_point['y']).' A'.get_path_point($circle_radius).' '.get_path_point($circle_radius).', 0, 0, 0, '.get_path_point($end_point['x']).' '.get_path_point($end_point['y']);
                    }
                }
            }
            $junction_row['d'] .= ' '.implode(' ',$path_d_end);
        }
    }
    unset($junction_row);
    foreach($line_set as $line_row_index=>&$line_row)
    {
        $line_row['track'] = [];
        $line_position = 1;
        foreach ($line_row['path'] as $path_endpoint_index=>$path_endpoint)
        {
            if ($path_endpoint_index == 0)
            {
                $line_row['track'][] = ['x'=>$path_endpoint[0],'y'=>$path_endpoint[1]];
                continue;
            }
            while (!empty($line_row['stop'][$line_position]) AND $line_row['stop'][$line_position]['x'] >= min($path_endpoint[0], $line_row['path'][$path_endpoint_index-1][0]) AND $line_row['stop'][$line_position]['x'] <= max($path_endpoint[0], $line_row['path'][$path_endpoint_index-1][0]) AND $line_row['stop'][$line_position]['y'] >= min($path_endpoint[1], $line_row['path'][$path_endpoint_index-1][1]) AND $line_row['stop'][$line_position]['y'] <= max($path_endpoint[1], $line_row['path'][$path_endpoint_index-1][1]))
            {
                $line_row['track'][] = ['x'=>floatval($line_row['stop'][$line_position]['x']),'y'=>floatval($line_row['stop'][$line_position]['y']),'stop_id'=>intval($line_row['stop'][$line_position]['id'])];
//                print_r($line_row['track']);print_r($line_row['stop'][$line_position]);exit;
                $line_position++;
            }
            $line_row['track'][] = ['x'=>$path_endpoint[0],'y'=>$path_endpoint[1]];
        }
    }
    unset($line_row);
?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Top4 Service Map</title>
</head>
<body>
<style>
    .svg_drawing_wrapper {font-size: 10px;}
    .svg_drawing_container
    {
        display: inline-block;
        position: relative;
        /*background: url("background.jpg");*/
        /*background-size: cover;*/
        font-size: <?=$container_font_size?>em;
    }
    .svg_drawing
    {
        width: <?=$canvas_size/10?>em;
        height: <?=round($canvas_size/$canvas_ratio/10,0)?>em;

        /*background: rgba(255,255,255,0.3);*/
    }
    .svg_drawing_container_animation_active .svg_drawing > *
    {
        opacity: 0.2;
    }
    .svg_drawing_container_animation_active .svg_drawing .line_animation_active
    {
        opacity: 1;
    }
    .line_name_container
    {
        display:block;
        position: absolute;
        padding: 0.2em 0.5em;
        margin: -1.7em 0 0 -3.5em;

        border-radius: 0.4em;

        color: #ffffff;
        font-size: <?=$line_name_font_size?>em;
        text-align: center;
        text-transform: uppercase;

        cursor: pointer;

        -webkit-transition:opacity 500ms ease;
        -moz-transition:opacity 500ms ease;
        -ms-transition:opacity 500ms ease;
        -o-transition:opacity 500ms ease;
        transition:opacity 500ms ease;

        z-index: 110;
    }
    .line_name
    {
        display: table-cell;
        width: 6em;
        height: 3em;
        vertical-align: middle;
    }
    .stop_name_container
    {
        display:block;
        position: absolute;
        margin: -1.25em 0 0 -3.5em;

        border-radius: 0.4em;

        font-size: <?=$stop_name_font_size?>em;
        text-transform: capitalize;

        vertical-align:top;

        -webkit-transition:opacity 500ms ease;
        -moz-transition:opacity 500ms ease;
        -ms-transition:opacity 500ms ease;
        -o-transition:opacity 500ms ease;
        transition:opacity 500ms ease;

        z-index: 110;
    }
    .stop_name
    {
        display: table-cell;
        width: 7em;
        height: 2.5em;
        vertical-align: inherit;
    }
    .svg_drawing_container_animation_active .line_name_container,
    .svg_drawing_container_animation_active .stop_name_container
    {
        opacity: 0.2;
    }
    .svg_drawing_container_animation_active .line_animation_active
    {
        opacity: 1;
    }
    .line_legendary_container
    {
        display: block;
        position: absolute;
        padding: 0.8em 1em;
        bottom: 0;
        left: 0;

        background: #ffffff;

        border:0.2em solid #9371ae;
        border-radius:1em;
    }
    .line_legendary
    {
        margin-bottom: 0.2em;

        cursor: pointer;
    }
    .line_legendary .line_name_container
    {
        display: inline-block;
        width: 4em;
        height: 1em;
        position: relative;
        margin: 0 1em 0 0;

        vertical-align: middle;
    }
<?php
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo PHP_EOL.'.line_'.$line_row_index.'.line_name_container {background: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo PHP_EOL.'.line_'.$line_row_index.'.stop_name_container {color: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo PHP_EOL.'#line_legendary_'.$line_row_index.' {color: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
    }
?>
    .stop_name_container.junction_stop_name_container
    {
        margin-left:-2.5em;
        color: black;
        font-size: <?=$junction_stop_name_font_size?>em;
        font-weight: bold;
    }
    .stop_name_container.junction_stop_name_container .stop_name
    {
        width: 5em;
    }
</style>
<!--<svg width="1800" height="1500" xmlns="http://www.w3.org/2000/svg" fill="transparent">-->
    <!--&lt;!&ndash;<path d="M1140 1070 L1140 900 L850 900 L850 870 L600 870 L600 600 L770 600" stroke="black" stroke-width="6" />&ndash;&gt;-->
    <!--<path d="M1140 1070 L1140 920 C1140 900, 1140 900, 1120 900 L850 900 L850 870 L600 870 L600 600 L770 600" stroke="#f06793" stroke-width="6" />-->
    <!--<circle r="8" cx="1140" cy="1010" stroke="black" stroke-width="5" fill="white" />-->
    <!--<text x="1160" y="1015" fill="#f06793">Hosting</text>-->

    <!--&lt;!&ndash;<path d="M1132 1010 C1132 1021.3, 1148 1021.3, 1148 1010 C1148 998.7, 1132 998.7, 1132 1010 Z" stroke="black" stroke-width="5" fill="white" />&ndash;&gt;-->


    <!--&lt;!&ndash;<path d="M1100 900 C1100 908, 1116 908, 1116 900 L1116 850 C1116 842, 1100 842, 1100 850 Z" stroke="black" stroke-width="5" fill="white" />&ndash;&gt;-->
    <!--<path d="M1100 900 A8 8, 0, 0, 0, 1116 900 L1116 850 A8 8, 0, 0, 0, 1100 850 Z" stroke="black" stroke-width="5" fill="white" />-->
    <!--<path d="M1002.5 592.4 A8 8, 0, 0, 0, 997.5 607.6 L1012.5 612.6 A8 8, 0, 0, 0, 1017.5 597.4 Z" stroke="black" stroke-width="5" fill="white" />-->

<!--</svg>-->
<!--<canvas id="canvas_bg" width="--><?//=$canvas_size?><!--" height="--><?//=round($canvas_size/$canvas_ratio,0)?><!--"></canvas>-->
<div class="svg_drawing_wrapper">
    <div class="svg_drawing_container">
        <svg class="svg_drawing" xmlns="http://www.w3.org/2000/svg" fill="transparent" viewBox="0 0 1000 800">
<?php
    // Draw Line
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo PHP_EOL.'<path class="line_'.$line_row_index.'" stroke="rgb('.implode(',',$line_row['color']).')" stroke-width="'.get_path_point($stroke_width).'" d="'.$line_row['d'].'" />'.PHP_EOL;
    }
    // Draw Stop Circles
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if ($stop_row['junction_id'] == 0)
        {
            echo PHP_EOL.'<circle class="line_'.$stop_row['line_id'].'" stroke="black" stroke-width="'.get_path_point($stroke_width).'" fill="white" cx="'.$stop_row['cx'].'" cy="'.$stop_row['cy'].'" r="'.get_path_point($circle_radius).'" />'.PHP_EOL;
        }
    }
    foreach($junction_set as $junction_row_index=>$junction_row)
    {
        $line_class = 'line_'.implode(' line_',$junction_row['line_id']);
        if (empty($junction_row['d']))
        {
            echo PHP_EOL.'<circle class="'.$line_class.'" stroke="black" stroke-width="'.get_path_point($stroke_width).'" fill="white" cx="'.$junction_row['cx'].'" cy="'.$junction_row['cy'].'" r="'.get_path_point($circle_radius).'" />'.PHP_EOL;
        }
        else
        {
            echo PHP_EOL.'<path class="'.$line_class.'" stroke="black" stroke-width="'.get_path_point($stroke_width).'" fill="white"  d="'.$junction_row['d'].'" />'.PHP_EOL;
        }
    }
?>
        </svg>
<?php
    // Line Name
    foreach($line_set as $line_row_index=>$line_row)
    {
        if (!empty($line_row['name']))
        {
            echo PHP_EOL.'<div class="line_name_container line_'.$line_row_index.'" style="top: '.($line_row['path'][0][1]*100*$canvas_ratio).'%; left: '.($line_row['path'][0][0]*100).'%;"><div class="line_name">'.$line_row['name'].'</div></div>'.PHP_EOL;
        }
        if (!empty($line_row['alternate_name']))
        {
            echo PHP_EOL.'<div class="line_name_container end_line_name_container line_'.$line_row_index.'" style="top: '.(end($line_row['path'])[1]*100*$canvas_ratio).'%; left: '.(end($line_row['path'])[0]*100).'%;"><div class="line_name">'.$line_row['alternate_name'].'</div></div>'.PHP_EOL;
        }
    }
    // Stop Name
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if ($stop_row['junction_id'] == 0)
        {
            echo PHP_EOL.'<div class="stop_name_container line_'.$stop_row['line_id'].'" style="'.$stop_row['text_location'].'"><div class="stop_name">'.$stop_row['name'].'</div></div>'.PHP_EOL;
        }
    }
    // Junction Name
    foreach($junction_set as $junction_row_index=>$junction_row)
    {
        $line_class = 'line_'.implode(' line_',$junction_row['line_id']);
        echo PHP_EOL.'<div class="stop_name_container junction_stop_name_container '.$line_class.'" style="'.$junction_row['text_location'].'"><div class="stop_name">'.$junction_row['name'].'</div></div>'.PHP_EOL;
    }
?>
        <div class="line_legendary_container">
<?php
    // Line Name
    foreach($line_set as $line_row_index=>$line_row)
    {
        if (!empty($line_row['name']))
        {
            echo PHP_EOL.'<div id="line_legendary_'.$line_row_index.'" class="line_legendary"><div class="line_name_container line_'.$line_row_index.'"></div>'.$line_row['name'].'</div>'.PHP_EOL;
        }
    }
?>
        </div>
    </div>
</div>
<table id="point_table">
</table>
<script src="jquery-1.11.3.js" type="application/javascript"></script>
<script type="application/javascript">
//    var svg_width = <?//=$canvas_size?>//;
//    $('.svg_drawing_container').click(function(event){
//console.log($('#canvas_bg').offset());
//        $('#point_table').append('<tr><td>'+(event.pageX-$('.svg_drawing_container').offset().left)+'</td><td>'+(event.pageY-$('.svg_drawing_container').offset().top)+'</td></tr>')
//    });
    $('.line_legendary').click(function(event){
        console.log($(this).data('line'));
        $('.svg_drawing_container').addClass('svg_drawing_container_animation_active');
        $('.line_'+$(this).data('line').id).css({'opacity':1});
        $('.svg_drawing .line_'+$(this).data('line').id).addClass('line_animation_active')
        console.log($('.svg_drawing .line_'+$(this).data('line').id));

    });
    $(document).ready(function(){
<?php
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo '$(\'#line_legendary_'.$line_row['id'].'\').data(\'line\','.json_encode($line_row).');'.PHP_EOL;
    }

?>
    });
</script>
</body>
</html>