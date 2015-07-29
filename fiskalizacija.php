<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');   


  class Fiskalizacija extends MY_Controller
  {
      public function __construct()
      {
        parent::__construct();
        
        $this->load->model('fiskal');   
        require_once('fiskalizator/Fiskalizator.php');  
       
      }
     
     
     public function fiskal()
     {
        $zahtjev = $this->input->post('request');

        //Init XML 
        $doc = new DOMDocument(); 
        $doc->formatOutput = true;      
          

        //loadaj pripremljeni xml prazan
        //$xml_string = file_get_contents('racun.xml'); 
        //$doc->loadXML($xml_string);
         
        //ubaci vrijednosti u strukturu xml.a   
        if($zahtjev == "PoslovniProstorZahtjev")       
        { 
            //id poslovnog prostora dohvati podatke
            $id = $this->input->post('id'); 
            
            $poslovniProstor = $this->fiskal->getPoslovniProstor($id);
            
            
                   
            $ns = 'tns';
            $writer = new XMLWriter();
            $writer->openMemory();
            $writer->startDocument('1.0', 'UTF-8');

            $writer->setIndent(4);
                
            $writer->startElementNs($ns, 'PoslovniProstorZahtjev', 'http://www.apis-it.hr/fin/2012/types/f73');     

            $writer->startElementNs($ns, 'PoslovniProstor', null);
            $writer->writeElementNs($ns, 'Oib', null, $poslovniProstor->pm_oib);
            $writer->writeElementNs($ns, 'OznPoslProstora', null, $poslovniProstor->pm_oznaka);

            $writer->startElementNs($ns, 'AdresniPodatak', null);
            
            if($poslovniProstor->pm_ostaliTipovi)
            {
                $writer->writeElementNs($ns, 'OstaliTipoviPP', null, $poslovniProstor->pm_ostaliTipovi);
              
            }
            else
            {
                $writer->startElementNs($ns, 'Adresa', null);
                $writer->writeElementNs($ns, 'Ulica', null, $poslovniProstor->pm_ulica);
                $writer->writeElementNs($ns, 'KucniBroj', null, $poslovniProstor->pm_kucniBroj);
                if($poslovniProstor->pm_kucniBrojDodatak)
                {
                    $writer->writeElementNs($ns, 'KucniBrojDodatak', null, $poslovniProstor->pm_kucniBrojDodatak);    
                }
                
                $writer->writeElementNs($ns, 'BrojPoste', null, $poslovniProstor->pm_posta);
                $writer->writeElementNs($ns, 'Naselje', null, $poslovniProstor->pm_mjesto);
                $writer->writeElementNs($ns, 'Opcina', null, $poslovniProstor->pm_opcina);
                $writer->endElement(); /* #Adresa */ 
            }
       
            
            $writer->endElement(); /* #AdresniPodatak */

            $writer->writeElementNs($ns, 'RadnoVrijeme', null, $poslovniProstor->pm_radnoVrijeme);
            $writer->writeElementNs($ns, 'DatumPocetkaPrimjene', null, date("d.m.Y", strtotime($poslovniProstor->pm_datumPocetkaPrimjene)));
            if($poslovniProstor->pm_oznakaZatvaranja)
            {
                $writer->writeElementNs($ns, 'OznakaZatvaranja', null, 'Z');
            }
            $writer->writeElementNs($ns, 'SpecNamj', null, $poslovniProstor->pm_oibProizvodjacaSoftvera); /* YOUR DEVELOPMENT COMPANY OIB ALWAYS */

            $writer->endElement(); /* #PoslovniProstor */


            $writer->endElement(); /* #PoslovniProstorZahtjev */

            //$writer->endDocument();

            $XMLRequest = $writer->outputMemory();


            $doc->loadXML($XMLRequest);          
        } 
          
        else if($zahtjev == "RacunZahtjev")     
        {
            $id = $this->input->post('id');     

            $firma = $this->fiskal->getById('firma', 'fi_id', $this->session->userdata('firmaID'));         
            $prikaziStavke = $this->fiskal->getstavke($id);
            $zaglavlje = $this->fiskal->getDokumentZag($id);
            $rekapitualcijaporeza = $this->fiskal->rekapitulacijaporeza($id);           
            
            $ns = 'tns';

            $writer = new XMLWriter();
            $writer->openMemory();
            $writer->startDocument('1.0', 'UTF-8');
            $writer->setIndent(True);                 
            $writer->startElementNs($ns, 'RacunZahtjev', null); 


            $writer->writeAttributeNS("xmlns","tns", null, "http://www.apis-it.hr/fin/2012/types/f73"); 
            $writer->writeAttributeNS("xmlns","xsi", null, "http://www.w3.org/2001/XMLSchema-instance"); 
            
            $writer->startElementNs($ns, 'Racun', null);
                $writer->writeElementNs($ns, 'Oib', null, $firma->fi_oib);
                $writer->writeElementNs($ns, 'USustPdv', null, $firma->fi_usustavuPDV);
                $writer->writeElementNs($ns, 'DatVrijeme', null, date("d.m.Y", strtotime($zaglavlje->do_datum)).date("\Th:i:s", strtotime($zaglavlje->do_vrijeme))); // date('d.m.Y\Th:i:s'));
                $writer->writeElementNs($ns, 'OznSlijed', null, 'N'); /* P ili N => P na nivou Poslovnog prostora, N na nivou naplatnog uredaja */                
                
                $writer->startElementNs($ns, 'BrRac', null);
                    $writer->writeElementNs($ns, 'BrOznRac', null, $zaglavlje->do_broj);
                    $writer->writeElementNs($ns, 'OznPosPr', null, $zaglavlje->PP);
                    $writer->writeElementNs($ns, 'OznNapUr', null, $zaglavlje->NU);
                $writer->endElement(); /* #BrRac */
               

                $writer->startElementNs($ns, 'Pdv', null); 
                       
                    if (!empty($rekapitualcijaporeza)) {                           
                        $rank = 0; foreach ($rekapitualcijaporeza as $porez) {
                                 
                                $writer->startElementNs($ns, 'Porez', null);   
                                $writer->writeElementNs($ns, 'Stopa', null, $porez->porez_pz_posto);
                                $writer->writeElementNs($ns, 'Osnovica', null, $porez->sumaIznosa);
                                $writer->writeElementNs($ns, 'Iznos', null, number_format($porez->sumaPorez, 2,'.',''));
                                $writer->endElement(); /* #Porez */                                                               
                        }
                    } 
                    
                $writer->endElement(); /* #Pdv */  
                
                if (!empty($rekapitualcijaporeza)) {                           
                    $rank = 0; foreach ($rekapitualcijaporeza as $porez) {
                        if($porez->porez_pz_posto == "0")
                        {
                            $writer->writeElementNs($ns, 'IznosOslobPdv', null, number_format($porez->sumaIznosa + $porez->sumaPorez, 2,'.',''));
                        }                              
                    }
                }               
                //$writer->writeElementNs($ns, 'IznosNePodlOpor', null, number_format($zaglavlje->do_iznos + $zaglavlje->do_iznosPDV,  2,'.',''));
                
                $writer->writeElementNs($ns, 'IznosUkupno', null, number_format($zaglavlje->do_iznos + $zaglavlje->do_iznosPDV,  2,'.',''));

                $writer->writeElementNs($ns, 'NacinPlac', null, $zaglavlje->sp_oznaka);
                $writer->writeElementNs($ns, 'OibOper', null, $zaglavlje->op_oib);

                $writer->writeElementNs($ns, 'NakDost', null, '0');

            $writer->endElement(); /* #Racun */

            $writer->endElement(); /* #RacunZahtjev */

            $writer->endDocument();

            $XMLRequest = $writer->outputMemory(); 

            $doc->loadXML($XMLRequest); 
                                           
            //file_put_contents("racun1.xml",$XMLRequest);
        }
        
        
        //dohvati podatke o firmi
        $firma =  $this->fiskal->getFirma();
           
        if($firma != FALSE)
        {
           
            try 
            {
                $fis = new Fiskalizator($firma->fi_certifikat, $this->decrypt($firma->fi_pass));   
                
                //Produkcijski mode
                //$fis->setProductionMode();
                
                $fis->doRequest($doc);
                
                #custom timeout and number of retries on network error, default is 3 retries and 5 seconds timeout tolerance
                #$fis->doRequest($doc, 10, 5.2);
                if($fis->getRequestType() == 'PoslovniProstorZahtjev' )
                {
                    echo json_encode(array('uspjelo'=>'1', 'poruka' => '<pre class="bg-success">Uspje&#353;no prijavljen poslovni prostor!</pre>')); 
                    
                    //update poslovni prostor
                    if($poslovniProstor->pm_oznakaZatvaranja)
                    {
                        $dok['pm_datumZatvaranja'] = $poslovniProstor->pm_datumPocetkaPrimjene;
                        $dok['pm_zatvoreno'] = 1;
                    }
                    else if(!$poslovniProstor->pm_datumOtvaranja)
                    {
                          $dok['pm_datumOtvaranja'] = $poslovniProstor->pm_datumPocetkaPrimjene;
                    }
                    
                    $dok['pm_datumRegistracije'] = date('Y-m-d');
                                      
                    $this->fiskal->update("prodajnomjesto", "pm_id", $this->input->post('id'), $dok); 
                    
                }
                else if ($fis->getRequestType() == 'RacunZahtjev')
                {
                    //echo 'JIR: '.$fis->getJIR().'<br>';
                    //echo 'ZKI: '.$fis->getZKI().'<br>'; 
                    
                    echo json_encode(array('uspjelo'=>'1', 'poruka' => '<pre class="bg-success">Uspje&#353;no fiskaliziran dokument!</pre>')); 
                    
                    //update raèun 

                    $dok['do_zki'] = $fis->getZKI();
                    $dok['do_jir'] = $fis->getJIR();

                    $this->fiskal->update("dokument", "do_id", $this->input->post('id'), $dok); 
                }
            
            } 
            catch (Exception $e) 
            {
                if ($zahtjev == 'RacunZahtjev')
                {                
                    //update raèun 

                    $dok['do_zki'] = $fis->getZKI();  
                    $dok['do_sifragreske'] = $e->getMessage();  

                    $this->fiskal->update("dokument", "do_id", $this->input->post('id'), $dok); 
                }
                
                echo json_encode(array('uspjelo'=>'0', 'poruka' => '<pre class="bg-danger">Gre&#353;ka </br>'.$e->getMessage().'</pre>'));  
            }  
           
        }
        else
        {
            echo json_encode(array('uspjelo'=>'0', 'poruka' => '<pre class="bg-danger">Morate dodati datoteku certifikata!</pre>'));
        }
   
      
    }
    
    
    
    function fiskalsve()
    {
        $zahtjev = $this->input->post('request');  
        
         $msg = '';
         $uspjelo = 0;
         $nijeuspjelo = 0;
                         
        //$firma = $this->fiskal->getById('firma', 'fi_id', $this->session->userdata('firmaID'));         
        $firma =  $this->fiskal->getFirma();         
        //dohvati podatke za sve  nefiskalizirane
        $nefiskalizirani = $this->fiskal->getNefiskalizirane();
        
        //foreach ID
      
        if($nefiskalizirani)
        {
            foreach ($nefiskalizirani as $dokument)
            {
                //dohvati pojedinacno   
                $prikaziStavke = $this->fiskal->getstavke($dokument->do_id);
                $zaglavlje = $this->fiskal->getDokumentZag($dokument->do_id);
                $rekapitualcijaporeza = $this->fiskal->rekapitulacijaporeza($dokument->do_id);              

                //Init XML 
                $doc = new DOMDocument(); 
                $doc->formatOutput = true;      
                
                //XML
                 $ns = 'tns';

                $writer = new XMLWriter();
                $writer->openMemory();
                $writer->startDocument('1.0', 'UTF-8');
                $writer->setIndent(True);                 
                $writer->startElementNs($ns, 'RacunZahtjev', null); 


                $writer->writeAttributeNS("xmlns","tns", null, "http://www.apis-it.hr/fin/2012/types/f73"); 
                $writer->writeAttributeNS("xmlns","xsi", null, "http://www.w3.org/2001/XMLSchema-instance"); 
                
                $writer->startElementNs($ns, 'Racun', null);
                    $writer->writeElementNs($ns, 'Oib', null, $firma->fi_oib);
                    $writer->writeElementNs($ns, 'USustPdv', null, $firma->fi_usustavuPDV);
                    $writer->writeElementNs($ns, 'DatVrijeme', null, date("d.m.Y", strtotime($zaglavlje->do_datum)).date("\Th:i:s", strtotime($zaglavlje->do_vrijeme))); // date('d.m.Y\Th:i:s'));
                    $writer->writeElementNs($ns, 'OznSlijed', null, 'N'); /* P ili N => P na nivou Poslovnog prostora, N na nivou naplatnog uredaja */                
                    
                    $writer->startElementNs($ns, 'BrRac', null);
                        $writer->writeElementNs($ns, 'BrOznRac', null, $zaglavlje->do_broj);
                        $writer->writeElementNs($ns, 'OznPosPr', null, $zaglavlje->PP);
                        $writer->writeElementNs($ns, 'OznNapUr', null, $zaglavlje->NU);
                    $writer->endElement(); /* #BrRac */
                   

                    $writer->startElementNs($ns, 'Pdv', null); 
                           
                        if (!empty($rekapitualcijaporeza)) {                           
                            $rank = 0; foreach ($rekapitualcijaporeza as $porez) {
                                     
                                    $writer->startElementNs($ns, 'Porez', null);   
                                    $writer->writeElementNs($ns, 'Stopa', null, $porez->porez_pz_posto);
                                    $writer->writeElementNs($ns, 'Osnovica', null, $porez->sumaIznosa);
                                    $writer->writeElementNs($ns, 'Iznos', null, number_format($porez->sumaPorez, 2,'.',''));
                                    $writer->endElement(); /* #Porez */                                                               
                            }
                        } 
                        
                    $writer->endElement(); /* #Pdv */  
                    
                    if (!empty($rekapitualcijaporeza)) {                           
                        $rank = 0; foreach ($rekapitualcijaporeza as $porez) {
                            if($porez->porez_pz_posto == "0")
                            {
                                $writer->writeElementNs($ns, 'IznosOslobPdv', null, number_format($porez->sumaIznosa + $porez->sumaPorez, 2,'.',''));
                            }                              
                        }
                    }               
                    //$writer->writeElementNs($ns, 'IznosNePodlOpor', null, number_format($zaglavlje->do_iznos + $zaglavlje->do_iznosPDV,  2,'.',''));
                    
                    $writer->writeElementNs($ns, 'IznosUkupno', null, number_format($zaglavlje->do_iznos + $zaglavlje->do_iznosPDV,  2,'.',''));
                                                                                                      
                    $writer->writeElementNs($ns, 'NacinPlac', null, $zaglavlje->sp_oznaka);
                    
                    $writer->writeElementNs($ns, 'OibOper', null, $zaglavlje->op_oib);

                    $writer->writeElementNs($ns, 'NakDost', null, '1');

                $writer->endElement(); /* #Racun */

                $writer->endElement(); /* #RacunZahtjev */

                $writer->endDocument();

                $XMLRequest = $writer->outputMemory(); 

                $doc->loadXML($XMLRequest);
                
                 //file_put_contents("racun".$dokument->do_id.".xml",$XMLRequest);  
                
              
                //fiskaliziraj
              
           
                if($firma != FALSE)
                {
                    
                   
                   
                    try 
                    {
                        $fis = new Fiskalizator($firma->fi_certifikat, $this->decrypt($firma->fi_pass));   
                        
                        //Produkcijski mode
                        //$fis->setProductionMode();
                        
                        $fis->doRequest($doc);
                        
                        #custom timeout and number of retries on network error, default is 3 retries and 5 seconds timeout tolerance
                        #$fis->doRequest($doc, 10, 5.2);
                        if ($fis->getRequestType() == 'RacunZahtjev')
                        {
                            //echo 'JIR: '.$fis->getJIR().'<br>';
                            //echo 'ZKI: '.$fis->getZKI().'<br>'; 
                             
                            $msg =  '<pre class="bg-success">Uspje&#353;no fiskalizirani svi dokument!</pre>';
                            $uspjelo = $uspjelo + 1;
                            
                            //update raèun 

                            $dok['do_zki'] = $fis->getZKI();
                            $dok['do_jir'] = $fis->getJIR();

                            $this->fiskal->update("dokument", "do_id", $zaglavlje->do_id, $dok); 
                        }
                    
                    } 
                    catch (Exception $e) 
                    {
                        if ($zahtjev == 'RacunZahtjev')
                        {                
                            //update raèun 

                            $dok['do_zki'] = $fis->getZKI();  
                            $dok['do_sifragreske'] = $e->getMessage();  

                            $this->fiskal->update("dokument", "do_id", $zaglavlje->do_id, $dok); 
                        }
                        
                        $msg =  '<pre class="bg-danger">Gre&#353;ka </br>'.$e->getMessage().'</pre>';
                        $nijeuspjelo = $nijeuspjelo + 1;  
                    }  
                   
                }
               
            }  
            
        }
        
        
        if($nijeuspjelo == 0)
        {
            echo json_encode(array('uspjelo'=> $uspjelo, 'poruka' => '<div class="alert alert-success">Uspje&#353;no fiskaliziranih ra&#269;una: <b>'.$uspjelo.'!</b></div>'));
        }   
        else
        {
            echo json_encode(array('uspjelo'=> $uspjelo, 'poruka' => '<div class="alert alert-danger">Nespje&#353;no fiskaliziranih ra&#269;una: <b>'.$nijeuspjelo.'</b> , uspje&#353;no fiskalizirano <b>' .$uspjelo.'</b></div>'));
        }
    
    }
    
     

    
    function decrypt($input) {   
        return trim( base64_decode($input));
    }  
}


   
?>
