<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');     

class Fiskal extends My_model {


    public function getFirma(){
        $sql="SELECT firma.* FROM firma
            where firma.fi_certifikat != '' and firma.fi_pass != '' and  firma.fi_id =". $this->session->userdata('firmaID');
       
        $query = $this->db->query($sql);
        $result = $query->result();
        if($result)
        {
            return $result[0];
        }
        else
        {
            return FALSE;
        }  
    } 
    
    
    public function getPoslovniProstor($id)
    {
        $sql="SELECT prodajnomjesto.* FROM prodajnomjesto
            where pm_id =" .$id." and firma_fi_id =". $this->session->userdata('firmaID');
       
        $query = $this->db->query($sql);
        $result = $query->result();
        return $result[0];
    } 
    
    
        
    public function getDokumentZag($id){
        $sql="SELECT dokument.*, partner.pa_naziv as partner, concat(operater.op_ime,' ', operater.op_prezime) as operater, operater.op_oib, vrstadokumenta.vd_id, vrstadokumenta.vd_oznaka, naplatniuredjaj.nu_broj as NU, prodajnomjesto.pm_oznaka as PP, sredstvoplacanja.sp_oznaka, sredstvoplacanja.sp_fiskalizirati  FROM dokument JOIN sredstvoplacanja ON sredstvoplacanja.sp_id = dokument.sredstvoplacanja_sp_id JOIN operater ON dokument.operater_op_id = operater.op_id  JOIN vrstadokumenta ON vrstadokumenta.vd_id = dokument.vrstadokumenta_vd_id  LEFT JOIN prodajnomjesto on prodajnomjesto.pm_id = dokument.prodajnoMjesto_pm_id LEFT JOIN naplatniuredjaj on naplatniuredjaj.nu_ID = dokument.naplatniuredjaj_nu_id  left outer join partner on partner.pa_id = dokument.Partner_pa_id 
        where dokument.firma_fi_id =". $this->session->userdata('firmaID')." and dokument.do_id =".$id;

        $query = $this->db->query($sql);
        $result = $query->result();
        return $result[0];        

    }   
    
    
    function getstavke($id){  
                  
        $sql="SELECT stavkedokumenta.*, artikl.ar_naziv as naziv, artikl.ar_opis from stavkedokumenta left outer JOIN artikl on artikl.ar_id = stavkedokumenta.artikl_ar_ID 
        where dokument_do_id =" .$id;

        $query = $this->db->query($sql);
        return $query->result();       

    }      
    
    public function rekapitulacijaporeza($id)
    {    
         $sql="SELECT sum(sd_iznosneto) as sumaIznosa,  sum(sd_poreziznos) as sumaPorez, porez_pz_posto FROM stavkedokumenta where dokument_do_id ='{$id}' group by porez_pz_posto order by porez_pz_posto desc";

         $query = $this->db->query($sql);
         return $query->result(); 
                        
    } 
    
    
    public function getNefiskalizirane(){
        $sql="SELECT dokument.do_id  FROM dokument  JOIN vrstadokumenta ON vrstadokumenta.vd_id = dokument.vrstadokumenta_vd_id join sredstvoplacanja on sredstvoplacanja.sp_id = dokument.sredstvoplacanja_sp_id
        where vrstadokumenta.vd_id = 2 and (dokument.do_status ='Z' or dokument.do_status = 'S') and (dokument.do_jir ='' or dokument.do_jir is null) and dokument.firma_fi_id =". $this->session->userdata('firmaID')." and sredstvoplacanja.sp_fiskalizirati != 0 order by dokument.do_broj desc";

        $query = $this->db->query($sql);

        return $query->result();   
        
    }       
    
    
    public function getDokumentZaglavlje($id) {
        $this->db->select('operater.*, firma.fi_naziv, firma.fi_id');
        $this->db->from('operater');
        $this->db->join('firma', 'firma.fi_id = operater.firma_fi_id'); 
        $this->db->where('operater.op_id', $id);


        $query = $this->db->get();
        $result = $query->result();
        $osoba = $result[0];

        $data = array(
            'is_logged_in'      =>  1,
            'id_osoba'          =>  $osoba->op_id,
            'email'             =>  $osoba->op_mail,
            'ime'               =>  $osoba->op_ime,
            'prezime'           =>  $osoba->op_prezime,
            'firma'             =>  $osoba->fi_naziv,
            'firmaID'           =>  $osoba->fi_id,
            'nivo'              =>  $osoba->op_nivo,
            'slika'             =>  $osoba->op_avatar
        );
        $this->session->set_userdata($data);
    }
}

?>