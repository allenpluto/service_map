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

    $stop_description_container_width = 20;

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
            'line_position'=>$stop_fetch_row['line_position'],
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
        $stop_row['description_location'] =  'left:'.($stop_fetch_row['point_x']*100).'%;bottom:'.((1/$canvas_ratio-$stop_fetch_row['point_y']+$circle_radius*4)*100*$canvas_ratio).'%';


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
        $junction_row['stop_id'] = [];
        $junction_row['line_id'] = [];

        $center_point_set = [];
        foreach($junction_row['stop'] as $junction_stop_index=>$junction_stop)
        {
            $junction_row['line_id'][] = $junction_stop['line_id'];
            $junction_row['stop_id'][] = $junction_stop['id'];
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
                $line_row['track'][] = ['x'=>floatval($line_row['stop'][$line_position]['x']),'y'=>floatval($line_row['stop'][$line_position]['y']),'stop_id'=>intval($line_row['stop'][$line_position]['id']),'line_position'=>$line_position];
//                print_r($line_row['track']);print_r($line_row['stop'][$line_position]);exit;
                $line_position++;
                if (end($line_row['track'])['x'] == $path_endpoint[0] AND end($line_row['track'])['y'] == $path_endpoint[1])
                {
                    continue 2;
                }
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
    .svg_drawing_wrapper {font-size: 10px;font-family: Arial;}
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

        background: #ffffff;
    }
    .svg_drawing_container_animation_active .svg_drawing > *
    {
        opacity: 0.2;
    }
    .line_stop
    {
        cursor: pointer;
    }
    .line_stop:hover
    {
        fill: rgb(240,88,39)
    }
    .line_stop_highlight
    {
        fill: rgb(236,30,43);
    }
    .svg_drawing_container_animation_active .line_stop
    {
        cursor: default;;
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

        cursor: pointer;

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
        cursor: default;;
        opacity: 0.2;
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
        echo '.line_'.$line_row_index.'.line_name_container {background: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo '.line_'.$line_row_index.'.stop_name_container {color: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo '#line_legendary_'.$line_row_index.' {color: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo '.svg_drawing_container_animation_active.line_active_'.$line_row_index.' .line_'.$line_row_index.' {opacity: 1;}'.PHP_EOL;
        echo '.svg_drawing_container_animation_active.line_active_'.$line_row_index.' .line_stop.line_'.$line_row_index.' {cursor: pointer;}'.PHP_EOL;
        echo '.svg_drawing_container_animation_active.line_active_'.$line_row_index.' .line_name_container.line_'.$line_row_index.' {cursor: pointer;}'.PHP_EOL;
        echo '.svg_drawing_container_animation_active.line_active_'.$line_row_index.' .stop_name_container.line_'.$line_row_index.' {cursor: pointer;}'.PHP_EOL;
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
    .stop_description_container
    {
        display: block;
        position: absolute;
        margin-left: -<?=$stop_description_container_width/2?>em;

        opacity: 0;
        z-index: -1;

        -webkit-transition:opacity 500ms ease;
        -moz-transition:opacity 500ms ease;
        -ms-transition:opacity 500ms ease;
        -o-transition:opacity 500ms ease;
        transition:opacity 500ms ease;
    }

    .stop_description_container.show_description
    {
        opacity: 1;
        z-index: 200;
    }

    .stop_description
    {
        display: block;
        width: <?=$stop_description_container_width?>em;
        position: relative;
        padding: 0.5em 1em;

        color: rgb(11,134,67);
        background: #ffffff;

        border: 2px solid #cccccc;
        border-radius: 1em;

        box-sizing: border-box;
    }
    .stop_description:before,
    .stop_description:after
    {
        display: block;
        width: 0;
        height: 0;
        position: absolute;
        top: 100%;
        left: 50%;

        margin-left: -<?=get_path_point(2*$circle_radius)?>px;

        border: <?=get_path_point(2*$circle_radius)?>px solid transparent;

        content: ' ';

        z-index: 220;
    }

    .stop_description:before
    {
        margin-top: 2px;
        border-top-color: #cccccc;
    }

    .stop_description:after
    {
        border-top-color: #eeeeee;
    }

    .stop_description_title
    {
        padding-bottom: 0.5em;

        font-size: 1.2em;
        font-weight: bold;
    }

    .stop_description_content > *
    {
        margin: 0 0 0.5em 0;
    }

    .line_info_container
    {
        display: block;
        width: 18em;
        height: 30em;
        position: absolute;
        padding: 0.8em 1em;
        bottom: 0;
        right: 0;

        background: #ffffff;

        border:0.2em solid #eeeeee;
        border-radius:1em;

        opacity: 0;
        z-index: -1;

        -webkit-transition:opacity 500ms ease;
        -moz-transition:opacity 500ms ease;
        -ms-transition:opacity 500ms ease;
        -o-transition:opacity 500ms ease;
        transition:opacity 500ms ease;

        overflow: auto;
    }

    .line_info_container_display
    {
        opacity: 1;
        z-index: 120;
    }

    .line_info_title
    {
        display: block;
        width: 100%;
        padding-bottom: 0.5em;

        font-size: 1.2em;
        font-weight: bold;
    }

    .train_animation
    {
        display: block;
        width: 3em;
        height: 3em;
        position: absolute;
        margin: -1.5em 0 0 -1.5em;

        background: url('business-man.png');
        background-size: cover;

        z-index: 300;
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
        <svg class="svg_drawing" xmlns="http://www.w3.org/2000/svg" fill="transparent" viewBox="0 0 <?=$canvas_size?> <?=$canvas_size/$canvas_ratio?>">
<?php
    // Draw Line
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo '<path class="line_'.$line_row_index.'" stroke="rgb('.implode(',',$line_row['color']).')" stroke-width="'.get_path_point($stroke_width).'" d="'.$line_row['d'].'" />'.PHP_EOL;
    }
    // Draw Stop Circles
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if ($stop_row['junction_id'] == 0)
        {
            echo '<circle id="line_stop_'.$stop_row['id'].'" class="line_stop line_'.$stop_row['line_id'].'" stroke="black" stroke-width="'.get_path_point($stroke_width/2).'" fill="white" cx="'.$stop_row['cx'].'" cy="'.$stop_row['cy'].'" r="'.get_path_point($circle_radius).'" data-line_id="'.$stop_row['line_id'].'" data-stop_id="'.$stop_row['id'].'" />'.PHP_EOL;
        }
    }
    foreach($junction_set as $junction_row_index=>$junction_row)
    {
        $line_class = 'line_'.implode(' line_',$junction_row['line_id']);
        if (empty($junction_row['d']))
        {
            echo '<circle id="line_stop_'.$junction_row['junction_id'].'" class="line_stop line_junction '.$line_class.'" stroke="black" stroke-width="'.get_path_point($stroke_width/2).'" fill="white" cx="'.$junction_row['cx'].'" cy="'.$junction_row['cy'].'" r="'.get_path_point($circle_radius).'" data-line_id="['.implode(',',$junction_row['line_id']).']" data-stop_id="['.implode(',',$junction_row['stop_id']).']" data-junction_id="'.$junction_row['junction_id'].'" />'.PHP_EOL;
        }
        else
        {
            echo '<path id="line_stop_'.$junction_row['junction_id'].'" class="line_stop line_junction '.$line_class.'" stroke="black" stroke-width="'.get_path_point($stroke_width/2).'" fill="white"  d="'.$junction_row['d'].'" data-line_id="['.implode(',',$junction_row['line_id']).']" data-stop_id="['.implode(',',$junction_row['stop_id']).']" data-junction_id="'.$junction_row['junction_id'].'" />'.PHP_EOL;
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
            echo PHP_EOL.'<div class="line_name_container line_'.$line_row_index.'" style="top: '.($line_row['path'][0][1]*100*$canvas_ratio).'%; left: '.($line_row['path'][0][0]*100).'%;" data-line_id="'.$line_row_index.'"><div class="line_name">'.$line_row['name'].'</div></div>'.PHP_EOL;
        }
        if (!empty($line_row['alternate_name']))
        {
            echo PHP_EOL.'<div class="line_name_container end_line_name_container line_'.$line_row_index.'" style="top: '.(end($line_row['path'])[1]*100*$canvas_ratio).'%; left: '.(end($line_row['path'])[0]*100).'%;" data-line_id="'.$line_row_index.'"><div class="line_name">'.$line_row['alternate_name'].'</div></div>'.PHP_EOL;
        }
        if (!empty($line_row['content']))
        {

        }
    }
    // Stop Name
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if ($stop_row['junction_id'] == 0)
        {
            echo PHP_EOL.'<div class="stop_name_container line_'.$stop_row['line_id'].'" style="'.$stop_row['text_location'].'" data-line_id="'.$stop_row['line_id'].'" data-stop_id="'.$stop_row['id'].'"><div class="stop_name">'.$stop_row['name'].'</div></div>'.PHP_EOL;
        }
    }
    // Junction Name
    foreach($junction_set as $junction_row_index=>$junction_row)
    {
        $line_class = 'line_'.implode(' line_',$junction_row['line_id']);
        echo PHP_EOL.'<div class="stop_name_container junction_stop_name_container '.$line_class.'" style="'.$junction_row['text_location'].'" data-line_id="['.implode(',',$junction_row['line_id']).']" data-stop_id="['.implode(',',$junction_row['stop_id']).']" data-junction_id="'.$junction_row['junction_id'].'"><div class="stop_name">'.$junction_row['name'].'</div></div>'.PHP_EOL;
    }
    // Tooltip
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if (!empty($stop_row['name']))
        {
            echo PHP_EOL.'<div id="stop_description_container_'.$stop_row['id'].'" class="stop_description_container line_'.$stop_row['line_id'].'" style="'.$stop_row['description_location'].'"><div class="stop_description"><div class="stop_description_title">'.$stop_row['name'].'</div><div class="stop_description_content">'.$stop_row['description'].'</div></div></div>'.PHP_EOL;
        }
    }
?>
        <div class="line_legendary_container">
<?php
    // Line Name
    foreach($line_set as $line_row_index=>$line_row)
    {
        if (!empty($line_row['name']))
        {
            echo PHP_EOL.'<div id="line_legendary_'.$line_row_index.'" class="line_legendary" data-line_id="'.$line_row['id'].'"><div class="line_name_container line_'.$line_row_index.'"></div>'.$line_row['name'].'</div>'.PHP_EOL;
        }
    }
?>
        </div>
<?php
     // Line Name
    foreach($line_set as $line_row_index=>$line_row)
    {
        if (!empty($line_row['name']))
        {
            echo PHP_EOL.'<div id="line_info_container_'.$line_row_index.'" class="line_info_container" data-line_id="'.$line_row['id'].'"><div class="line_info_title">'.$line_row['name'].'</div><div class="line_info_content">'.$line_row['content'].'</div></div>'.PHP_EOL;
        }
    }
?>
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


    $(document).ready(function(){
        $('.svg_drawing_container').data('canvas',{'canvas_size':<?=$canvas_size?>,'canvas_ratio':<?=$canvas_ratio?>});
        $('.svg_drawing_container').data('line',<?=json_encode($line_set)?>);
        $('.svg_drawing_container').data('stop',<?=json_encode($stop_set)?>);
        $('.svg_drawing_container').data('junction',<?=json_encode($junction_set)?>);
        $('.svg_drawing_container').data('active_line',[]);
        $('.svg_drawing_container').data('animation',{'line_id':0,'direction':1,'stop_id':0,'position':0});
        $('.svg_drawing_container').data('animate_on',false);

        $('.svg_drawing_container').on('start_animation', function() {
            if ($('.svg_drawing_container').data('animate_on'))
            {
                setTimeout(function(){
                    $('.svg_drawing_container').trigger('start_animation');
                },1000);
                return false;
            }
            var canvas_data = $('.svg_drawing_container').data('canvas');
            var line_data = $('.svg_drawing_container').data('line');
            var active_line = $('.svg_drawing_container').data('active_line');
            var animation_data = $('.svg_drawing_container').data('animation');

            if ($('.svg_drawing_container').data('animation_timer'))
            {
                clearTimeout($('.svg_drawing_container').data('animation_timer'));
                $('.svg_drawing_container').data('animation_timer','');
            }

            if (active_line.length == 0)
            {
                active_line = [];
                Object.keys(line_data).forEach(function(line_id){
                    active_line.push(parseInt(line_id));
                });
            }
            if (active_line.indexOf(animation_data['line_id']) == -1)
            {
                animation_data['line_id'] = parseInt(active_line[Math.floor(Math.random() * active_line.length)]);
                animation_data['direction'] = 1;
                animation_data['position'] = 0;
                if (line_data[animation_data['line_id']]['alternate_name'])
                {
                    animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;    // random direction -1 or 1
                    if (animation_data['direction'] == -1)
                    {
                        animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
                    }
                }
                $('.svg_drawing_container').trigger('display_line_info',[animation_data['line_id']]);
            }
            $('.train_animation').remove();
            $('<div />',{
                'class':'train_animation'
            }).css({
                'left':(line_data[animation_data['line_id']]['track'][animation_data['position']]['x']*100)+'%',
                'top':(line_data[animation_data['line_id']]['track'][animation_data['position']]['y']*100*canvas_data['canvas_ratio'])+'%'
            }).appendTo('.svg_drawing_container');
            $('.svg_drawing_container').data('animation',animation_data);
            $('.svg_drawing_container').trigger('trigger_animation');
        });
        $('.svg_drawing_container').on('trigger_animation', function() {
            var canvas_data = $('.svg_drawing_container').data('canvas');
            var line_data = $('.svg_drawing_container').data('line');
            var stop_data = $('.svg_drawing_container').data('stop');
            var junction_data = $('.svg_drawing_container').data('junction');
            var active_line = $('.svg_drawing_container').data('active_line');
            var animation_data = $('.svg_drawing_container').data('animation');
            var speed = 0.00003; //travel % of canvas width per milli-second
            var pause_time = 5000;
            var easing = 'swing';

            if ($('.svg_drawing_container').data('animation_timer'))
            {
                clearTimeout($('.svg_drawing_container').data('animation_timer'));
                $('.svg_drawing_container').data('animation_timer','');
            }

            if (active_line.length == 0)
            {
                active_line = [];
                Object.keys(line_data).forEach(function(line_id){
                    active_line.push(parseInt(line_id));
                });
            }
            if (!animation_data['stop_id'])
            {
                pause_time = 0;
                easing = 'linear';
            }
            else
            {
                if (stop_data[animation_data['stop_id']]['junction_id'] > 0)
                {
                    var stop_in_junction = junction_data[stop_data[animation_data['stop_id']]['junction_id']]['stop_id'];
                    var stop_available = [];
                    stop_in_junction.forEach(function(stop_id_in_junction, index){
                        if (active_line.indexOf(parseInt(stop_data[stop_id_in_junction]['line_id'])) > -1)
                        {
                            stop_available.push(stop_id_in_junction);
                        }
                    });
                    animation_data['stop_id'] = parseInt(stop_available[Math.floor(Math.random() * stop_available.length)]);
                    if (animation_data['line_id'] != parseInt(stop_data[animation_data['stop_id']]['line_id']))
                    {
                        animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;
                        $('.svg_drawing_container').trigger('display_line_info',[stop_data[animation_data['stop_id']]['line_id']]);
                    }
                    animation_data['line_id'] = parseInt(stop_data[animation_data['stop_id']]['line_id']);
                    line_data[animation_data['line_id']]['track'].forEach(function(track_point,track_index){
                        if (track_point['stop_id'] == animation_data['stop_id'])
                        {
                            animation_data['position'] = track_index;
                            $('.train_animation').animate({
                                'left':(track_point['x']*100)+'%',
                                'top':(track_point['y']*100*canvas_data['canvas_ratio'])+'%'
                            },500);
                        }
                    });

                }
            }
            // Reach the end of line
            if (animation_data['direction'] == 1)
            {
                if (animation_data['position'] >= line_data[animation_data['line_id']]['track'].length - 1)
                {
                    if (active_line.length > 1 && animation_data['stop_id'] == 0)
                    {
                        setTimeout(function(){
                            if (animation_data['line_id'] == 5)
                            {
                                animation_data['line_id'] = 6;
                                animation_data['direction'] = 1;
                                animation_data['position'] = 0;
                            }
                            else
                            {
                                animation_data['line_id'] = parseInt(active_line[Math.floor(Math.random() * active_line.length)]);
                                animation_data['direction'] = 1;
                                animation_data['position'] = 0;
                                if (line_data[animation_data['line_id']]['alternate_name'])
                                {
                                    animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;    // random direction -1 or 1
                                    if (animation_data['direction'] == -1)
                                    {
                                        animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
                                    }
                                }
                            }
                            $('.svg_drawing_container').data('animation',animation_data);
                            $('.svg_drawing_container').trigger('display_line_info',[animation_data['line_id']]);
                            $('.svg_drawing_container').trigger('start_animation');
                        },1000);
                        return false;
                    }
                    else
                    {
                        animation_data['direction'] = -1;
                        pause_time = 1000;
                    }
                }
            }
            else
            {
                if (animation_data['position'] <= 0)
                {
                    if (active_line.length > 1 && animation_data['stop_id'] == 0)
                    {
                        setTimeout(function(){
                            if (animation_data['line_id'] == 6)
                            {
                                animation_data['line_id'] = 5;
                                animation_data['direction'] = -1;
                                animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
                            }
                            else
                            {
                                animation_data['line_id'] = parseInt(active_line[Math.floor(Math.random() * active_line.length)]);
                                animation_data['direction'] = 1;
                                animation_data['position'] = 0;
                                if (line_data[animation_data['line_id']]['alternate_name'])
                                {
                                    animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;    // random direction -1 or 1
                                    if (animation_data['direction'] == -1)
                                    {
                                        animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
                                    }
                                }
                            }
                            $('.svg_drawing_container').data('animation',animation_data);
                            $('.svg_drawing_container').trigger('display_line_info',[animation_data['line_id']]);
                            $('.svg_drawing_container').trigger('start_animation');
                        },1000);
                        return false;
                    }
                    else
                    {
                        animation_data['direction'] = 1;
                        pause_time = 1000;
                    }
                }
            }
            var next_line = animation_data['line_id'];
            var next_position = animation_data['position']+animation_data['direction'];
            var distance = Math.sqrt(Math.pow(line_data[animation_data['line_id']]['track'][animation_data['position']]['x']-line_data[next_line]['track'][next_position]['x'],2) + Math.pow(line_data[animation_data['line_id']]['track'][animation_data['position']]['y']-line_data[next_line]['track'][next_position]['y'],2));

            var animation_timer = setTimeout(function(){
                $('.svg_drawing_container').data('animate_on',true);
                $('.train_animation').animate({
                    'left':(line_data[next_line]['track'][next_position]['x']*100)+'%',
                    'top':(line_data[next_line]['track'][next_position]['y']*100*canvas_data['canvas_ratio'])+'%'
                },distance/speed,easing,function(){
                    $('.svg_drawing_container').data('animate_on',false);
                    animation_data['line_id'] = next_line;
                    animation_data['position'] = next_position;
                    if (line_data[next_line]['track'][next_position]['stop_id'])
                    {
                        animation_data['stop_id'] = parseInt(line_data[next_line]['track'][next_position]['stop_id']);
                        $('.svg_drawing_container').trigger('display_tooltip',[animation_data['stop_id']]);
                    }
                    else
                    {
                        animation_data['stop_id'] = 0;
                    }
                    $('.svg_drawing_container').data('animation',animation_data);
                    $('.svg_drawing_container').trigger('trigger_animation');
                });
            },pause_time);
            $('.svg_drawing_container').data('animation_timer',animation_timer);
        });
        $('.svg_drawing_container').on('display_tooltip',function(event,stop_id){
//            var line_data = $('.svg_drawing_container').data('line');
            var active_line = $('.svg_drawing_container').data('active_line');
            var stop_data = $('.svg_drawing_container').data('stop');
            var stop = stop_data[stop_id];

            if (stop['junction_id'] > 0)
            {
                stop_id = stop['junction_id'];
                if (active_line.length > 0)
                {
                    var line_active = false;
                    var line_group = $('#line_stop_'+stop_id).data('line_id');
                    line_group.forEach(function(element,index){
                        if (active_line.indexOf(element) > -1)
                        {
                            line_active = true;
                        }
                    });
                    if (line_active === false)
                    {
                        return false;
                    }
                }
            }
            else
            {
                // If the line is not active, do not show tooltip
                if (active_line.length > 0 && active_line.indexOf(parseInt(stop['line_id'])) == -1)
                {
                    return false;
                }
            }
            $('.show_description').removeClass('show_description');
            var stop_description = $('#stop_description_container_'+stop_id);
            stop_description.addClass('show_description');
            setTimeout(function(){
                stop_description.removeClass('show_description');
            },5000);
        });
        $('.svg_drawing_container').on('display_line_info',function(event, line_id){
            var line_data = $('.svg_drawing_container').data('line');

            $('.line_info_container_display').removeClass('line_info_container_display');
            $('#line_info_container_'+line_id).addClass('line_info_container_display');
        });
        $('.svg_drawing_container').on('click',function(event){
            var clicked_element = $(event.target);
            var stop_data = $('.svg_drawing_container').data('stop');
            var line_data = $('.svg_drawing_container').data('line');
            var junction_data = $('.svg_drawing_container').data('junction');
            var active_line = $('.svg_drawing_container').data('active_line');
            var animation_data = $('.svg_drawing_container').data('animation');

            if (clicked_element.hasClass('stop_name'))
            {
                clicked_element = clicked_element.closest('.stop_name_container');
            }
            if (active_line.length == 0)
            {
                active_line = [];
                Object.keys(line_data).forEach(function(line_id){
                    active_line.push(parseInt(line_id));
                });
            }
            if (clicked_element.data('junction_id'))
            {
                $('.svg_drawing_container').trigger('display_tooltip',[clicked_element.data('junction_id')]);

                var stop_in_junction = junction_data[clicked_element.data('junction_id')]['stop_id'];
                var stop_available = [];
                stop_in_junction.forEach(function(stop_id_in_junction, index){
                    if (active_line.indexOf(stop_data[stop_id_in_junction]['line_id']) > -1)
                    {
                        stop_available.push(stop_id_in_junction);
                    }
                });
                animation_data['line_id'] = stop_data[clicked_element.data('junction_id')]['line_id'];
                animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;
                animation_data['position'] = 0;
                if (line_data[animation_data['line_id']]['alternate_name'])
                {
                    animation_data['direction'] = 1 - Math.floor(Math.random() * 2)*2;    // random direction -1 or 1
                    if (animation_data['direction'] == -1)
                    {
                        animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
                    }
                }
            }
            else
            {
                if (clicked_element.data('stop_id'))
                {
                    $('.svg_drawing_container').trigger('display_tooltip',[clicked_element.data('stop_id')]);
                }
            }
        });
        $('.line_legendary').click(function(event){
            var active_line = $('.svg_drawing_container').data('active_line');

            $('.svg_drawing_container').addClass('svg_drawing_container_animation_active');
            if ($('.svg_drawing_container').hasClass('line_active_'+$(this).data('line_id')))
            {
                var pos = active_line.indexOf($(this).data('line_id'));
                if (pos > -1)
                {
                    active_line.splice(pos,1);
                }
                $('.svg_drawing_container').removeClass('line_active_'+$(this).data('line_id'));
            }
            else
            {
                active_line.push($(this).data('line_id'));
                $('.svg_drawing_container').addClass('line_active_'+$(this).data('line_id'));
            }
            if (active_line.length == 0)
            {
                $('.svg_drawing_container').removeClass('svg_drawing_container_animation_active');
            }
            $('.svg_drawing_container').data('active_line',active_line);
            $('.svg_drawing_container').trigger('start_animation');
        });
        $('.line_name_container').click(function(event){
            var animation_data = $('.svg_drawing_container').data('animation');

            animation_data['line_id'] = $(this).data('line_id');
            animation_data['direction'] = 1;
            animation_data['position'] = 0;
            if ($(this).hasClass('end_line_name_container'))
            {
                animation_data['direction'] = -1;
                animation_data['position'] = line_data[animation_data['line_id']]['track'].length - 1;
            }
            $('.svg_drawing_container').trigger('display_line_info',[animation_data['line_id']]);
            $('.svg_drawing_container').trigger('start_animation');
        });
        $('.svg_drawing_container').trigger('start_animation');

    });
</script>
</body>
</html>