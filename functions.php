<?php
    getEventsGuide();
    $channels_array = getChannels();
    $out="";

    //Escric els valors des de la posició 1 fins a la posició n-1 (per evitar l'última fila)
    for ($i=1; $i<$number_rows-1; $i++)
    {
        //Comprovo que hi hagi més de 4 columnes. Sinó vol dir que hi ha un banner i no ho mostro
        if (count($data_table_rows[$i]->getElementsByTagName("td"))>4)
        {
            $out.= "<tr>";
            //Recorro les 4 primeres columnes. L'última, la dels canals, la tracto a banda
            for ($j=0; $j<=4; $j++)
            {
                $out.= "<td>";
                $out.= $data_table_rows[$i]->getElementsByTagName("td")[$j]->textContent;
                $out.= "</td>";
            }
            //Processo la columna de canals
            $out.= "<td>";
            $str = $data_table_rows[$i]->getElementsByTagName("td")[5]->textContent;

            /*
                Segons la documentació de php.net el primer valor de l'array que genera preg_match_all [0] és un
                subarray amb els valors que coincideixen amb el patró de l'expressió regular.
            */
            preg_match_all("/[0-9]+/",$str,$matches);;
            foreach ($matches[0] as $value)
            {
                $out.= " <a href='" . $channels_array[$value] . "'>[Canal ". $value . "]</a> ";
            }
            $out.= "</td>";
            $out.= "</tr>";
        }
    }

    echo $out;

    /* ------------------ FUNCIONS ------------------*/

    //En comptes de retornar cap resultat deso com a variables globals els dos valors que m'interessen: la quantitat de files de la taula i les propies dades de cada fila
    function getEventsGuide()
    {
        // Creo les capçaleres. Pel que he descobert cal enviar la cookie perquè cloudfare ens deixi obtenir la pàgina
        $options = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" . "Cookie: beget=begetok\r\n"
            )
        );

        $context = stream_context_create($options);

        // Obro la URL amb les capçaleres anteriors
        $URL = file_get_contents('http://arenavision.us/guide', false, $context);

        //Creo un objecte DOMDocument per poder recórrer la pàgina web com un arbre DOM
        $DOM = new DOMDocument('1.0', 'utf-8');
        $DOM->loadHTML($URL);

        //Recupero la taula. Si res canvia és l'única taula de la pàgina. Com que no té cap id l'he de recuperar per l'índex [0]
        $data_table = $DOM->getElementsByTagName("table")[0];

        //Obtinc totes les files de la taula
        $GLOBALS["data_table_rows"] = $data_table->getElementsByTagName("tr");

        //Calculo totes les files. Sé que la primra és la de títols i l'última hi posen la data d'actualització
        $GLOBALS["number_rows"] = count($GLOBALS["data_table_rows"]);
    }

    //Retorno la llista de canals en forma d'array associatiu on l'índex normalment coincideix amb la key (número de canal) però no té perquè ser sempre així
    function getChannels()
    {
        define("CHANNELS", 44);
        $channels_array = array();

        for ($i=1; $i<=CHANNELS; $i++)
        {
            //Per aconseguir dos dígits per xifa. Per exemple 1 és 01, etc.
            $current_channel = str_pad($i, 2, "0", STR_PAD_LEFT);

            $curl = curl_init("http://cdn.arenavision.biz/" . $current_channel);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

            $page = curl_exec($curl);
            $regex = "/acestream:\/\/[\w\d]+/i";
            if ( preg_match($regex, $page, $list) )
                $channels_array[$i] = $list[0];
        }

        return $channels_array;
    }
?>
