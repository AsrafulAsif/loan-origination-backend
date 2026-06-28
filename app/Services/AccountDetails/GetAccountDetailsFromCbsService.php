<?php

namespace App\Services\AccountDetails;

class GetAccountDetailsFromCbsService
{
    protected string $odbc_url_cbs;

    public function __construct()
    {
        $this->odbc_url_cbs = config('app.odbc_url');
    }


    public function getAccountInfo(string $bank_account_number): array
    {
        $sql = "select
        gfcun,aaztin, aazidn, aazead1, aazmphn, aazbdte,
        bgfnam, bgmnam, bgsnam, bgsex, bgnatn, bgprof,
        b0cn11, b0cn12, b0cn1a, b0cn17, b0cn18, b0cn19,
        cabbn, cabrn,
        bsbbrn, bsbbrt,
        svna1,svna2,svna3,svna4
        from gfpf
        left join nepf on nean=gfcpnc
        left join aazpf on aazcus=gfcpnc
        left join bgpf on bgcus=gfcpnc
        left join b0pf on b0ab=neab and b0an=gfcpnc and b0as=neas
        left join capf on cabbn=neab
        left join bspf on bsbrnm=cabrnm
        left join sxpf on sxcus=gfcus
        left join svpf on svseq=sxseq and sxprim=''
        where neean='$bank_account_number'";

        $url = $this->odbc_url_cbs;
        $postData = http_build_query(
            array(
                //'action' => 'EmployeeDetailsByEMEmail',
                'username' => 'superadmin',
                'password' => 'ZJH2FbP5RgnExRQt',
                'sql' => $sql,
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData,
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        $i = 0;
        if ($data['statusCode'] == '200') {
            if (@$data['data'][$i]['GFCUN'] != NULL) $gfcun = $data['data'][$i]['GFCUN']; else $gfcun = '';
            if (@$data['data'][$i]['BGFNAM'] != NULL) $bgfnam = $data['data'][$i]['BGFNAM']; else $bgfnam = '';
            if (@$data['data'][$i]['BGMNAM'] != NULL) $bgmnam = $data['data'][$i]['BGMNAM']; else $bgmnam = '';
            if (@$data['data'][$i]['BGSNAM'] != NULL) $bgsnam = $data['data'][$i]['BGSNAM']; else $bgsnam = '';
            if (@$data['data'][$i]['BGSEX'] != NULL) $bgsex = $data['data'][$i]['BGSEX']; else $bgsex = '';
            if (@$data['data'][$i]['BGNATN'] != NULL) $bgnatn = $data['data'][$i]['BGNATN']; else $bgnatn = '';
            if (@$data['data'][$i]['BGPROF'] != NULL) $bgprof = $data['data'][$i]['BGPROF']; else $bgprof = '';

            if (@$data['data'][$i]['AAZTIN'] != NULL) $aaztin = $data['data'][$i]['AAZTIN']; else $aaztin = '';
            if (@$data['data'][$i]['AAZIDN'] != NULL) $aazidn = $data['data'][$i]['AAZIDN']; else $aazidn = '';
            if (@$data['data'][$i]['AAZEAD1'] != NULL) $aazead1 = $data['data'][$i]['AAZEAD1']; else $aazead1 = '';
            if (@$data['data'][$i]['AAZMPHN'] != NULL) $aazmphn = $data['data'][$i]['AAZMPHN']; else $aazmphn = '';
            if (@$data['data'][$i]['AAZBDTE'] != NULL) $aazbdte = $data['data'][$i]['AAZBDTE']; else $aazbdte = '';

            if (@$data['data'][$i]['B0CN11'] != NULL) $b0cn11 = $data['data'][$i]['B0CN11']; else $b0cn11 = '';
            if (@$data['data'][$i]['B0CN1A'] != NULL) $b0cn1a = $data['data'][$i]['B0CN1A']; else $b0cn1a = '';
            if (@$data['data'][$i]['B0CN12'] != NULL) $b0cn12 = $data['data'][$i]['B0CN12']; else $b0cn12 = '';
            if (@$data['data'][$i]['B0CN17'] != NULL) $b0cn17 = $data['data'][$i]['B0CN17']; else $b0cn17 = '';
            if (@$data['data'][$i]['B0CN18'] != NULL) $b0cn18 = $data['data'][$i]['B0CN18']; else $b0cn18 = '';
            if (@$data['data'][$i]['B0CN19'] != NULL) $b0cn19 = $data['data'][$i]['B0CN19']; else $b0cn19 = '';

            if (@$data['data'][$i]['CABBN'] != NULL) $cabbn = $data['data'][$i]['CABBN']; else $cabbn = '';
            if (@$data['data'][$i]['CABRN'] != NULL) $cabrn = $data['data'][$i]['CABRN']; else $cabrn = '';
            if (@$data['data'][$i]['BSBBRN'] != NULL) $bsbbrn = $data['data'][$i]['BSBBRN']; else $bsbbrn = '';
            if (@$data['data'][$i]['BSBBRT'] != NULL) $bsbbrt = $data['data'][$i]['BSBBRT']; else $bsbbrt = '';

            if (@$data['data'][$i]['SVNA1'] != NULL) $svna1 = $data['data'][$i]['SVNA1']; else $svna1 = '';
            if (@$data['data'][$i]['SVNA2'] != NULL) $svna2 = $data['data'][$i]['SVNA2']; else $svna2 = '';
            if (@$data['data'][$i]['SVNA3'] != NULL) $svna3 = $data['data'][$i]['SVNA3']; else $svna3 = '';
            if (@$data['data'][$i]['SVNA4'] != NULL) $svna4 = $data['data'][$i]['SVNA4']; else $svna4 = '';

            return[
                'Full_Name'=> $gfcun,
                'Father_Name'=> $bgfnam,
                'Mother_Name'=> $bgmnam,
                'Spouse_Name'=> $bgsnam,
                'Gender'=> $bgsex == "M" ? "Male" : ($bgsex == "F" ? "Female" : "Other"),
                'Nationality'=> $bgnatn,
                'Profession'=> $bgprof,
                'TIN'=> $aaztin,
                'NID'=> $aazidn,
                'Email'=> $aazead1,
                'Mobile'=> $aazmphn,
                'DOB'=> $this->formatDate($aazbdte),
                'Name_of_Nominee'=> $b0cn11,
                'NID_of_Nominee'=> $b0cn1a,
                'DOB_of_Nominee'=> $this->formatDate($b0cn12),
                'Nominee_Address'=> $b0cn17,
                'Nominee_Profession'=> $b0cn18,
                'Nominee_Relation_with'=> $b0cn19,
                'Branch_Code'=> $cabbn,
                'Branch_Name'=> $cabrn,
                'Routing_Number'=> $bsbbrn,
                'Reporting_Branch'=> $bsbbrt,
                'Address_Line'=> ($svna1 ? $svna1: '') . ($svna2 ? ', ' . $svna2: '') . ($svna3 ? ', ' . $svna3: '') . ($svna4 ? ', ' . $svna4: ''),
            ];

        } else {
            abort(500,"Can not connect to server.");
        }
    }

    private function formatDate(string $dateString): ?string
    {
        if (!$dateString || strlen($dateString) < 7) {
            $dateString = '0'.$dateString;
        }



        // First digit defines century
        $centuryFlag = substr($dateString, 0, 1);

        // DDMMYY
        $year  = substr($dateString, 1, 2);
        $month = substr($dateString, 3, 2);
        $day   = substr($dateString, 5, 2);

        if ($centuryFlag === '1') {
            $fullYear = "20{$year}";
        } else {
            $fullYear = "19{$year}";
        }

        // Return YYYY-MM-DD
        return "{$fullYear}-{$month}-{$day}";
    }


}
