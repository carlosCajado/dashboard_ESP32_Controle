<?php
$mysqli = new mysqli("localhost", "root", "", "istrumentacao");

if (!$mysqli) {
    echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}
 
$result1 = mysqli_query($mysqli, "SELECT * from medicao order by id desc");
$result2 = mysqli_query($mysqli, "SELECT * FROM (SELECT * FROM medicao ORDER BY id DESC LIMIT 1000) AS t1 ORDER BY t1.id");
$result3 = mysqli_query($mysqli, "SELECT * FROM (SELECT * FROM medicao ORDER BY id DESC LIMIT 30) AS t1 ORDER BY t1.id");

$temperaturas = "<br/>";
$TempInicial = 0;

$linhas = mysqli_num_rows($result1);
$grafico2 = mysqli_num_rows($result2);
$grafico3 = mysqli_num_rows($result3);
if ($linhas) {     
    $array_temperaturas = array();
    $array_umidades = array();

    //Parametros para o grafico pizza
    $temperatura_aceitavel = 0;
    $temperatura_alerta = 0;
    $temperatura_critica= 0;

    $umidade_aceitavel = 0;
    $umidade_alerta = 0;
    $umidade_critica= 0;

	while ($linha = mysqli_fetch_array($result1)) {         

        if($TempInicial == 0){
            $TempInicial = $linha['temperatura'];
        }
        array_push($array_temperaturas, $linha['temperatura']);

        $umidade = $linha['umidade'];
        array_push($array_umidades, $umidade);
        $data = $linha['hora'];

        if($linha['temperatura'] <= 26){
            $temperatura_aceitavel = $temperatura_aceitavel + 1;
        }
        if($linha['temperatura'] > 26 && $linha['temperatura'] <= 40){
            $temperatura_alerta = $temperatura_alerta + 1;
        }if($linha['temperatura'] > 40){
            $temperatura_critica = $temperatura_critica + 1;
        }
        //verificando para umidade 
        if($umidade  <= 70){
            $umidade_aceitavel = $umidade_aceitavel + 1;
        }
        if($umidade  > 70 && $umidade  <= 80){
            $umidade_alerta = $umidade_alerta + 1;
        }
        if ($umidade  > 80){
            $umidade_critica = $umidade_critica + 1;
        }
    }
    $count_array_temperaturas = array_count_values($array_temperaturas);
    $count_array_umidades = array_count_values($array_umidades);
}
if($grafico3){
    $array_temp30 = array();
    $array_umi30 = array();
    while ($grafico3 = mysqli_fetch_array($result3)) {     
        $temperatura_geral = $grafico3['temperatura'];
        $umidade_geral = $grafico3['umidade'];
        array_push($array_temp30, $temperatura_geral);
        array_push($array_umi30, $umidade_geral);

    }
    $media_aritimetica_temperatura = number_format((array_sum($array_temp30) / count($array_temp30)), 1);
    $media_aritimetica_umidade = number_format((array_sum($array_umi30) / count($array_umi30)), 1);
}
mysqli_close($mysqli); //fecha a conexão com o banco de dados

?> 
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">

        <!--==================== UNICONS ====================-->
        <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">


        <!--==================== SWIPER CSS ====================-->
        <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">

        <!--==================== CSS ====================-->
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
            li{
                font-weight: bold;
            }
        </style>
            <title>WebTemp - Monitor de Temperatura</title>
            
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">       
            google.charts.load('current', {'packages':['gauge']});
            google.charts.setOnLoadCallback(drawChart);
            google.charts.setOnLoadCallback(drawChart2);
        
            function drawChart() {
        
                var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Cº', <?= $media_aritimetica_temperatura ?>],
                ]);
                var options = {
                width: 600, height: 150, max: 80,
                yellowFrom:28, yellowTo: 40,
                redFrom: 40, redTo: 80,          
                minorTicks: 5
                };
        
                var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
                chart.draw(data, options);
        
                
            }
             function drawChart2() {
        
                var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['%', <?= $media_aritimetica_umidade?>],
                ]);
        
                var options = {
                width: 600, height: 150, max: 100,
                yellowFrom:70, yellowTo: 80,
                redFrom: 80, redTo: 100,          
                minorTicks: 5
                };
        
                var chart = new google.visualization.Gauge(document.getElementById('chart_div2'));
        
                chart.draw(data, options);
        
                
            }
            </script>
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = google.visualization.arrayToDataTable([
            ['horas', 'Temperatura Cº', 'umidade %'],

                <?php

                    while ($grafico_result = mysqli_fetch_array($result2)) {     
                        $temperatura_geral = $grafico_result['temperatura'];
                        $umidade_geral = $grafico_result['umidade'];
                        $data_geral = $grafico_result['hora'];
                

                ?>
            ['<?php echo date('H:i d/m', strtotime($data_geral))?>',  <?php echo $temperatura_geral; ?>, <?php echo $umidade_geral?>],
            <?php } ?>

            ]);
            
            var options = {
            title: 'Histórico Temperatura/Umidade',
            curveType: 'function',
            legend: { position: 'bottom' }
            };
            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
            chart.draw(data, options);
        }
        </script>
        
        <!-- Grafico pizaa -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Recorrência nº vezes', 'Temperatura'],
          <?php

        foreach ($count_array_temperaturas as $valor => $value) {
            
        ?>
            [<?php echo $value?>,  <?php echo $valor?>, ],
            <?php } ?>
            
        ]);
        

        var options = {
          chart: {
            title: 'Frequência de Temperaturas',
            subtitle: 'nº vezes/Temperatura ',
          }
        };

        var chart = new google.charts.Bar(document.getElementById('columnchart_material'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }
    </script>

    <!-- pizza -->
    <script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {

    var data = google.visualization.arrayToDataTable([
      ['Parametro', 'Amostra'],
      ['Aceitável (inferior 26Cº)',   <?php echo $temperatura_aceitavel?>],
      ['Crítica (superior 40Cº)',     <?php echo $temperatura_critica?>],
      ['Atenção (entre 26ºC e 40Cº)',<?php echo $temperatura_alerta?>],
    ]);

    var options = {
      pieHole: 0.5,
      pieSliceTextStyle: {
        color: 'black',
      },
      title: 'Tendencia de Risco (Temperatura)',
      legend: 'block'
    };

    var chart = new google.visualization.PieChart(document.getElementById('donut_single'));
    chart.draw(data, options);
  }
  google.charts.setOnLoadCallback(drawChart_um);
    function drawChart_um() {

    var data = google.visualization.arrayToDataTable([
    ['Effort', 'Amount given'],
    ['Aceitável (inferior 70%)',  <?php echo $umidade_aceitavel?>],
    ['Crítica (superior 80%)',     <?php echo $umidade_critica?>],
    ['Atenção (entre 70% e 80%)',    <?php echo $umidade_alerta?>],
    ]);

    var options = {
    pieHole: 0.5,
    pieSliceTextStyle: {
        color: 'black',
    },
    title: 'Tendencia de Risco (Umidade)',
    legend: 'block'
    };

    var chart = new google.visualization.PieChart(document.getElementById('donut_single2'));
    chart.draw(data, options);
    }
</script>  
    </head>
    <body>
        <header class="header" id="header">
            <nav class="nav container">
                <!-- <div><a href="#" class="nav__logo" >Carlos  Cajado</a> -->
                </div>
                <div class="nav__menu" id="nav-menu">
                    <ul class="nav__list grid">
                        <li class="nav__item">
                            <a href="#dashboard" class="nav__link active-link">
                                <i class="uil uil-diamond nav__icon"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav__item">
                            <a href="#grafico" class="nav__link">
                                <i class="uil uil-rocket nav__icon"></i>Gáficos
                            </a>
                        </li>
                        <li class="nav__item">
                            <a href="#relatorio" class="nav__link">
                                <i class="uil uil-octagon nav__icon"></i>Relatórios
                            </a>
                        </li>
                        <li class="nav__item">
                            <a href="#ajustes" class="nav__link">
                                <i class="uil uil-bright nav__icon"></i>Ajustes
                            </a>
                        </li>
                    </ul>
                    <i class="uil uil-times nav__close" id="nav-close"></i>
                </div>
                <div class="nav_btns">
                    <div class="nav__toggle" id="nav-toggle">
                        <i class="uil uil-apps"></i>

                    </div>
                </div>
                <i class="uil uil-moon change-theme" id="theme-button"></i>
            </nav>

        </header>
        <?php echo("<meta http-equiv='refresh' content='60'>"); ?>
            <main class="main">
        <section class="about section" id="dashboard">
            <h2 class="section__title"> Sala dos Servidores</h2>
            <span class="section__subtitle">Controle Temperatura/Umidade</span>
            <div class="about__container container grid">
                    <div  style="width: 160px;">
                        <div>
                            <h3 class="qualification__title">Temperatura(Cº)</h3>
                        </div>
                        <div id="chart_div" style="width: 400px; height: 140px;"></div>
                        <br>
                        <div>
                            <h4 class="qualification__title">Umidade(%)</h4>
                        </div>
                        <div id="chart_div2" style="width: 400px; height: 140px;"></div>

                    </div>
                <div class="about__data">
                <p class="about__description">
                Seguindo as normas da Associação Americana dos Engenheiros de Aquecimento, 
                Refrigeração e Ar Condicionado os Servidores devem ser mantidos entre
                uma temperatura entre 15 graus Celsius e 32 graus C, bem como uma umidade relativa de 20% a 80%.                              
                </p>
                <p class="about__description">
                Atualizações Automática por minuto.<br> Em caso de resultados na faixa de alerta, Informar a equipe Resposável!!.                       
                </p>
                    <!-- <div class="about__buttons">
                        <form>
                        <input type="button" value="Precisa de Ajuda ?" onClick="history.go(0)" class="button button--flex" style=" background-color:brown;">
                        </form>
                    </div> -->
                </div>
            </div>
            <div id='clock' class="section__title"></div>
            <div class="about__buttons">
                    <form>
                        <input type="button" id="setTime" value="Atualização Manual" onClick="history.go(0)" class=" button button--flex">
                    </form> 
            </div>
        </section>
        <section class="skills section" id="grafico">
            <h2 class="section__title">Gáficos</h2>
            <span class="section__subtitle">Diferenciais</span>
            <div class="skills__container ">
                <div><div class="skills__content skills__close">
                    <div class="skills__header">
                        <i class="uil uil-emoji skills__icon"></i>

                        <div>
                            <h1 class="skills__title grid">Histórico</h1>
                        </div>
                        <i class="uil uil-angle-down skills__arrow"></i>
                    </div>

                    <div class="grid" style="justify-content: center; align-items: center;">
                        <div id="curve_chart" style="width: 1200px; height: 500px"></div>
                    </div>
                </div>
                    <div class="skills__content skills__close">
                        <div class="skills__header">
                            <i class="uil uil-brackets-curly skills__icon"></i>

                            <div>
                                <h1 class="skills__title">Extras</h1>
                            </div>
                            <i class="uil uil-angle-down skills__arrow"></i>
                        </div>
                        <div class=" grid skills__list" style="justify-content: center; align-items: center;">
                                <div class="about__container container grid">
                            <div  style="width: 400px;">
                                <div id="donut_single" style="width: 375px; height: 400px;"></div>
                            </div>
                        <div class="about__data">
                            <div id="donut_single2" style="width: 375px; height: 400px;"></div>
                        </div>
                    </div>
                    <div id="columnchart_material" style="width: 775px; height: 400px; "></div>
                </div>
            </div>
        </section>
        <section class="about section" id="relatorio">
            <h2 class="section__title">Relatório Estatísticos</h2>
            <span class="section__subtitle">Análise descritiva dos dados</span>
                <div class="about__container container grid">
                    <div  style="width:800px;">
                    <div class="about__buttons">

                    </div>
                        <form action="gerar_pdf.php">
                            <input type="submit" value="Gerar PDF"  class=" button button--flex"/>
                        </form>
                    </div>
                    <div class="about__data">
                            <dl>
                                <p class="about__description">
                                Listagem de todas as medições, temperatura e umidade relativa do ar, feitas pelo sensor ordenadas pela data e horário da aferição. 
                                Ademais,é fornecidos:
                                </p>
                                <li>
                                Valor Máximo
                                </li>
                                <li>
                                Valor Mínimo
                                </li>
                                <li>
                                Amplitude entre os valores
                                </li>
                                <li>
                                Média Aritimética
                                </li>
                                <li>
                                Desvio Padrão
                                </li>
                            </dl>                  
                    </div>
                    
                </div>
                
        </section>
            <section class="about section" id="ajustes">
            </section>
    </main>
    <!--==================== SCROLL TOP ====================-->
    <a href="#" class="scrollup" id="scroll-up">
        <i class="uil uil uil-arrow-circle-up scrollup__icon"></i>
    </a>

    <!--==================== SWIPER JS ====================-->
    <script src="assets/js/swiper-bundle.min.js"></script>

    <!--==================== MAIN JS ====================-->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/relogio.js"></script>
    </body>
</html>