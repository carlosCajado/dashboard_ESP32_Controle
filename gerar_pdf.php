<?php

// Carregar o Composer
require './vendor/autoload.php';

// Incluir
include_once './conexao.php';
include_once './estatistica.php';

// QUERY para recuperar os registros do banco de dados
$query_usuarios = "SELECT hora, temperatura, umidade FROM medicao order by hora desc";

// Prepara a QUERY
$result_usuarios = $conn->prepare($query_usuarios);

// Executar a QUERY
$result_usuarios->execute();

// Informacoes para o PDF
$dados = "<!DOCTYPE html>";
$dados .= "<html lang='pt-br'>";
$dados .= "<head>";
$dados .= "<meta charset='UTF-8'>";
$dados .= '      <style>
body {
   background: rgb(204, 204, 204);
   font-family:"Lucida Console", Monaco, monospace;
}

page {
   background: white;
   display: block;
   margin: 0 auto;
   margin-bottom: 0.5cm;
   box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
}

page[size="A4"] {
   width: 21cm;
   height: 29.7cm;
}

page[size="A4"][layout="portrait"] {
   width: 29.7cm;
   height: 21cm;
}

@media print {
   body,
   page {
      margin: 0;
      box-shadow: 0;
   }
}

.header{
   padding-top:10px;
   text-align: center;
   border: 2px solid #ddd;
}

table {
   border-collapse: collapse;
   width: 100%;
   font-size: 80%;
}
table th{
   background-color: #4caf50;
   text-align: center;
}
th, td {
   border: 1px solid #ddd;
   text-align: left;
   text-align: center;
}

tr:nth-child(even){
   background-color: #f2f2f2
}
.nrPage{
   text-align: right;
   margin-right: 10px;
}
.td-primaria {
    height: 50px;
  }
  .td-segundaria {
    height: 20px;
  }

</style>';
$dados .= "<title>Histórico De Variação</title>";
$dados .= "</head>";
$dados .= "<body>";
$dados .= "<page>";
$dataAtual = date('d/m/y');
$dados .= "<div class='header'>
         <div class='nrPage'>$dataAtual</div>
         Instrumentação Monitor
         <br> UFMA - SÃO LUIS - MA
         <br>
         <h3>Histórico De Variacão - Temperatura/Umidade</h3></h3>
        </div>";
        $dados .= "<table class='table'>";

        $dados .= "<thead>
              <tr>
                 <th class='td-segundaria'>Temperatura (Cº)</th>
                 <th class='td-segundaria'>Umidade do Ar (%)</th>
                 <th class='td-segundaria'>Data e Hora</th>
              </tr>
           </thead>";
  
        $dados .= "<tbody>";
        $array_temperaturas = array();
        $array_umidades = array();
        while($row_usuario = $result_usuarios->fetch(PDO::FETCH_ASSOC)){
            extract($row_usuario);

            // adicionado os valores em um array proprio:
            array_push($array_temperaturas, $temperatura);
            array_push($array_umidades, $umidade);

            $dados .= "<tr>";
            $dados .= "<td>$temperatura</td>";
            $dados .= "<td>$umidade%</td>";
            $dataHoraFormatada = date('d/m/y H:i:s', strtotime($hora));
            $dados .= "<td>$dataHoraFormatada</td>";
            $dados .= "</tr>";
        }
        //echo '<pre> ';
        //var_dump($array_temperaturas);

        //Estimativas amostrais:

        //***temperatura***
        $media_aritimetica_temperatura = number_format((array_sum($array_temperaturas) / count($array_temperaturas)), 4);
        $desvio_padrao_temperatura = number_format(stats_standard_deviation($array_temperaturas), 4);
        $maior_temperatura  = max($array_temperaturas);
        $menor_temperatura  = min($array_temperaturas);
        $Amplitude_temperatura = ($maior_temperatura - $menor_temperatura);

        // //***Umidade***
        $media_aritimetica_umidade = number_format((array_sum($array_umidades) / count($array_umidades)), 4);
        $desvio_padrao_umidade = number_format(stats_standard_deviation($array_umidades), 4);
        $maior_umidade  = max($array_umidades);
        $menor_umidade  = min($array_umidades);
        $Amplitude_umidade = $maior_umidade - $menor_umidade;

        $dados .= "</tbody>";
        $dados .= "</table>";
        $dados .= "<div class='header'>
        <h3>Estimativas amostrais</h3>
       </div>";

        $dados .= "<table class='table'>";
        $dados .= "
        <tr>
            <th class='td-segundaria'>&nbsp;</th>
            <th class='td-segundaria'>Valor Max</th>
            <th class='td-segundaria'>Valor Min</th>
            <th class='td-segundaria'>Amplitude</th>
            <th class='td-segundaria'>Média Aritimética</th>
            <th class='td-segundaria'>Desvio Padrão</th>
        </tr>";

        $dados .= "
        <tr>
            <td class='td-primaria'>Temperatura</td>
            <td>$maior_temperatura Cº</td>
            <td>$menor_temperatura Cº</td>
            <td>$Amplitude_temperatura Cº</td>
            <td>$media_aritimetica_temperatura Cº</td>
            <td>$desvio_padrao_temperatura</td>
        </tr>";
        $dados .= "
        <tr>
            <td class='td-primaria'>Umidade (%)</td>
            <td>$maior_umidade%</td>
            <td>$menor_umidade%</td>
            <td>$Amplitude_umidade%</td>
            <td>$media_aritimetica_umidade%</td>
            <td>$desvio_padrao_umidade</td>
        </tr>";
        $dados .= "</table>";
        $dados .= "</page>";

// Ler os registros retornado do BD
$dados .= "</body>";


// Referenciar o namespace Dompdf
use Dompdf\Dompdf;

// Instanciar e usar a classe dompdf
$dompdf = new Dompdf(['enable_remote' => true]);

// Instanciar o metodo loadHtml e enviar o conteudo do PDF
$dompdf->loadHtml($dados);

// Configurar o tamanho e a orientacao do papel
// landscape - Imprimir no formato paisagem
//$dompdf->setPaper('A4', 'landscape');
// portrait - Imprimir no formato retrato
$dompdf->setPaper('A4', 'portrait');

// Renderizar o HTML como PDF
$dompdf->render();

// Gerar o PDF
$dompdf->stream();
