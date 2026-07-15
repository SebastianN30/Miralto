<?php
///Cree un script que reciba un numero N e imprima una tabla N x N en la que cada una de sus posiciones esten llenas con numeros aleatorios den el rango N/3 - N(2)
function randomRable($number = null){

    $min = intval($number / 3);
    $max = $number ** 2;

    for($i= 0; $i < $number; $i++){
        for($interval = 0; $interval < $number; $interval ++){
            $sheetNumber = rand($min, $max);
            
            echo "<tr><td>". $sheetNumber . "</td></tr>"; 
        }
    }
    
    echo PHP_EOL;
}

randomRable(5);