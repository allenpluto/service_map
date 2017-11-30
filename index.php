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

    $line_name_font_size = 0.008;
    $stop_name_font_size = 0.008;
    $junction_stop_name_font_size = 0.01;

    function get_path_point($path_value)
    {
        return round($path_value*$GLOBALS['canvas_size'],2);
    }

    $line_set = [];
    foreach ($line_fetch as $line_fetch_row) {
        $line_row = [
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
            'name'=>$stop_fetch_row['name'],
            'description'=>$stop_fetch_row['description'],
            'line_id'=>$stop_fetch_row['line_id'],
            'junction_id'=>$stop_fetch_row['junction_id'],
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
            $stop_row['text_location'] .= 'left:'.get_path_point($stop_fetch_row['point_x']+$stop_fetch_row['text_x']).'px;';
        }
        else
        {
            $stop_row['text_location'] .= 'right:'.get_path_point(1-$stop_fetch_row['point_x']-$stop_fetch_row['text_x']).'px;text-align:right;';
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
            $stop_row['text_location'] .= 'top:'.get_path_point($stop_fetch_row['point_y']+$stop_fetch_row['text_y']).'px;';
        }
        else
        {
            $stop_row['text_location'] .= 'bottom:'.get_path_point(1/$canvas_ratio-$stop_fetch_row['point_y']-$stop_fetch_row['text_y']).'px;vertical-align:bottom;';
        }

        $stop_set[$stop_fetch_row['id']] = $stop_row;

        if ($stop_fetch_row['junction_id'] > 0)
        {
            $junction_set[$stop_fetch_row['junction_id']]['stop'][$stop_fetch_row['junction_position']] = $stop_row;
        }
    }
    foreach($junction_set as $junction_row_index=>&$junction_row)
    {
        ksort($junction_row['stop']);
        $junction_row['center'] = [];
        foreach($junction_row['stop'] as $junction_stop_index=>$junction_stop)
        {
            $center_point = ['x'=>$junction_stop['cx'],'y'=>$junction_stop['cy']];
            if ($junction_stop_index > 0)
            {
                if ($center_point['x'] == end($junction_row['center'])['x'] AND $center_point['y'] == end($junction_row['center'])['y'])
                {
                    continue;
                }
                if (count($junction_row['center']) > 2)
                {
                    // If previous point on the path from second but last point to new point, then remove the last point
                    if (($center_point['y'] == end($junction_row['center'])['y'] AND $center_point['y'] == $junction_row['center'][lenth($junction_row['center'])-2]['y']) OR ($center_point['x']-$junction_row['center'][lenth($junction_row['center'])-2]['x'])/($center_point['y']-$junction_row['center'][lenth($junction_row['center'])-2]['y']) == ($center_point['x']-end($junction_row['center'])['x'])/($center_point['y']-end($junction_row['center'])['y']))
                    {
                        array_pop($junction_row['center']);
                    }
                }
            }
            $junction_row['center'][] = $center_point;
        }
        if (count($junction_row['center']) > 1)
        {

        }
    }

?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Top4 Service Map</title>
</head>
<body>
<style>
    html, body {font-size: 10px;}
    #canvas_bg
    {
        width: <?=$canvas_size?>px;
        height: <?=round($canvas_size/$canvas_ratio,0)?>px;
    }
    .svg_drawing_container
    {
        display: inline-block;
        position: relative;
        /*background: url("background.jpg");*/
        /*background-size: cover;*/
    }
    .svg_drawing
    {
        width: <?=$canvas_size?>px;
        height: <?=round($canvas_size/$canvas_ratio,0)?>px;

        /*background: rgba(255,255,255,0.3);*/
    }
    .svg_mask
    {
        display: block;
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;

        z-index: 100;
    }
    .line_name_container
    {
        display:block;
        position: absolute;
        padding: 0.2em 0.5em;
        margin: -1.7em 0 0 -3.5em;

        border-radius: 0.4em;

        color: #ffffff;
        font-size: <?=$line_name_font_size*$canvas_size?>px;
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

        font-size: <?=$stop_name_font_size*$canvas_size?>px;
        text-transform: capitalize;

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
<?php
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo PHP_EOL.'.line_'.$line_row_index.'.line_name_container {background: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
        echo PHP_EOL.'.line_'.$line_row_index.'.stop_name_container {color: rgb('.implode(',',$line_row['color']).');}'.PHP_EOL;
    }
?>
    .stop_name_container.junction_stop_name_container
    {
        margin-left:-2.5em;
        color: black;
        font-size: <?=$junction_stop_name_font_size*$canvas_size?>px;
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
<div class="svg_drawing_container">
    <svg class="svg_drawing" xmlns="http://www.w3.org/2000/svg" fill="transparent">
<?php
    // Draw Line
    foreach($line_set as $line_row_index=>$line_row)
    {
        echo PHP_EOL.'<path class="line_'.$line_row_index.'" stroke="rgb('.implode(',',$line_row['color']).')" stroke-width="'.$canvas_size*$stroke_width.'" d="'.$line_row['d'].'" />'.PHP_EOL;
    }
    // Draw Stop Circles
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if ($stop_row['junction_id'] == 0)
        {
            echo PHP_EOL.'<circle class="line_'.$stop_row['line_id'].'" stroke="black" stroke-width="'.$canvas_size*$stroke_width.'" fill="white" cx="'.$stop_row['cx'].'" cy="'.$stop_row['cy'].'" r="'.$canvas_size*$circle_radius.'" />'.PHP_EOL;
        }
    }
    foreach($junction_set as $junction_row_index=>$junction_row)
    {
        $line_class = '';
        foreach($junction_row['stop'] as $junction_stop)
        {
            $line_class .= ' line_'.$junction_stop['line_id'];
        }
        if (count($junction_row['center']) == 1)
        {
            echo PHP_EOL.'<circle class="'.$line_class.'" stroke="black" stroke-width="'.$canvas_size*$stroke_width.'" fill="white" cx="'.end($junction_row['center'])['x'].'" cy="'.end($junction_row['center'])['y'].'" r="'.$canvas_size*$circle_radius.'" />'.PHP_EOL;
        }
    }
?>
    </svg>
    <div class="svg_mask">
<?php
    // Draw Line
    foreach($line_set as $line_row_index=>$line_row)
    {
        if (!empty($line_row['name']))
        {
            echo PHP_EOL.'<div class="line_name_container line_'.$line_row_index.'" style="background: rgb('.implode(',',$line_row['color']).'); top: '.get_path_point($line_row['path'][0][1]).'px; left: '.get_path_point($line_row['path'][0][0]).'px;"><div class="line_name">'.$line_row['name'].'</div></div>'.PHP_EOL;
        }
        if (!empty($line_row['alternate_name']))
        {
            echo PHP_EOL.'<div class="line_name_container end_line_name_container line_'.$line_row_index.'" style="background: rgb('.implode(',',$line_row['color']).'); top: '.get_path_point(end($line_row['path'])[1]).'px; left: '.get_path_point(end($line_row['path'])[0]).'px;"><div class="line_name">'.$line_row['alternate_name'].'</div></div>'.PHP_EOL;
        }
    }
    foreach($stop_set as $stop_row_index=>$stop_row)
    {
        if (!empty($stop_row['name']))
        {
            echo PHP_EOL.'<div class="stop_name_container line_'.$stop_row['line_id'].($stop_row['junction_id']>0?' junction_stop_name_container':'').'" style="'.$stop_row['text_location'].'"><div class="stop_name">'.$stop_row['name'].'</div></div>'.PHP_EOL;
        }
    }

?>
    </div>
</div>
<table id="point_table">
</table>
<script src="jquery-1.11.3.js" type="application/javascript"></script>
<script type="application/javascript">
    var svg_width = <?=$canvas_size?>;
    $('.svg_drawing_container').click(function(event){
console.log($('#canvas_bg').offset());
        $('#point_table').append('<tr><td>'+(event.pageX-$('.svg_drawing_container').offset().left)+'</td><td>'+(event.pageY-$('.svg_drawing_container').offset().top)+'</td></tr>')
    });
    $(document).ready(function(){
//        // from database
//        var line_set = <?//=json_encode($line_set)?>//;
//        line_set.forEach(function(line, index){
//            var d = '';
//            line.path.forEach(function(turning_point){
//                if (!d)
//                {
//                    d = 'M'+turning_point[0]*svg_width+' '+turning_point[1]*svg_width
//                }
//                else
//                {
//                    d += ' L'+turning_point[0]*svg_width+' '+turning_point[1]*svg_width
//                }
//            });
//            $('.svg_drawing')[0].innerHTML += '<path class="line_'+index+'" stroke="rgba('+line.color.join()+',1)" stroke-width="<?//=$canvas_size*$stroke_width?>//" d="'+line.d+'" />';
//            if (line.name)
//            {
//                var line_name_temp = $('<div />',{
//                    'class':'line_name_container line_'+index
//                }).css({
//                    'background':'rgba('+line.color.join()+',1)'
//                }).html('<div class="line_name">'+line.name+'</div>');
//                line_name_temp.appendTo('.svg_mask').css({
//                    'top':Math.floor(line.path[0][1]*svg_width),
//                    'left':Math.floor(line.path[0][0]*svg_width),
//                    'opacity':1
//                });
//            }
//            if (line.alternate_name)
//            {
//                var line_name_temp = $('<div />',{
//                    'class':'line_name_container line_name_end line_'+index
//                }).css({
//                    'background':'rgba('+line.color.join()+',1)'
//                }).html('<div class="line_name">'+line.alternate_name+'</div>');
//                line_name_temp.appendTo('.svg_mask').css({
//                    'top':Math.floor(line.path[line.path.length-1][1]*svg_width),
//                    'left':Math.floor(line.path[line.path.length-1][0]*svg_width),
//                    'opacity':1
//                });
//            }
//
//        });

    });
</script>
</body>
</html>