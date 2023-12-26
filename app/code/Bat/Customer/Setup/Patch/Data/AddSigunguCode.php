<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Customer\Setup\Patch\Data;

use Bat\Customer\Model\SigunguCodeFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * @class AddSigunguCode
 * Add sigungu codes
 */
class AddSigunguCode implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var SigunguCodeFactory
     */
    private SigunguCodeFactory $sigunguCodeFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SigunguCodeFactory $sigunguCodeFactory
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        SigunguCodeFactory $sigunguCodeFactory
    ) {
        $this->sigunguCodeFactory = $sigunguCodeFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        $sigunguCodes = [
            ["depot"=>"KB","sigungu_code"=>"11110","city"=>"서울특별시 종로구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11140","city"=>"서울특별시 중구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11170","city"=>"서울특별시 용산구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11200","city"=>"서울특별시 성동구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11215","city"=>"서울특별시 광진구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11230","city"=>"서울특별시 동대문구","tax_code"=>"A100"],
            ["depot"=>"BB","sigungu_code"=>"11260","city"=>"서울특별시 중랑구","tax_code"=>"A100"],
            ["depot"=>"BB","sigungu_code"=>"11290","city"=>"서울특별시 성북구","tax_code"=>"A100"],
            ["depot"=>"BB","sigungu_code"=>"11305","city"=>"서울특별시 강북구","tax_code"=>"A100"],
            ["depot"=>"BB","sigungu_code"=>"11320","city"=>"서울특별시 도봉구","tax_code"=>"A100"],
            ["depot"=>"BB","sigungu_code"=>"11350","city"=>"서울특별시 노원구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11380","city"=>"서울특별시 은평구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11410","city"=>"서울특별시 서대문구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11440","city"=>"서울특별시 마포구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11470","city"=>"서울특별시 양천구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11500","city"=>"서울특별시 강서구","tax_code"=>"A100"],
            ["depot"=>"GP","sigungu_code"=>"11530","city"=>"서울특별시 구로구","tax_code"=>"A100"],
            ["depot"=>"GP","sigungu_code"=>"11545","city"=>"서울특별시 금천구","tax_code"=>"A100"],
            ["depot"=>"KB","sigungu_code"=>"11560","city"=>"서울특별시 영등포구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11590","city"=>"서울특별시 동작구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11620","city"=>"서울특별시 관악구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11650","city"=>"서울특별시 서초구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11680","city"=>"서울특별시 강남구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11710","city"=>"서울특별시 송파구","tax_code"=>"A100"],
            ["depot"=>"KN","sigungu_code"=>"11740","city"=>"서울특별시 강동구","tax_code"=>"A100"],
            ["depot"=>"PB","sigungu_code"=>"26110","city"=>"부산광역시 중구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26140","city"=>"부산광역시 서구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26170","city"=>"부산광역시 동구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26200","city"=>"부산광역시 영도구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26230","city"=>"부산광역시 부산진구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26260","city"=>"부산광역시 동래구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26290","city"=>"부산광역시 남구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26320","city"=>"부산광역시 북구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26350","city"=>"부산광역시 해운대구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26380","city"=>"부산광역시 사하구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26410","city"=>"부산광역시 금정구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26440","city"=>"부산광역시 강서구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26470","city"=>"부산광역시 연제구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26500","city"=>"부산광역시 수영구","tax_code"=>"B100"],
            ["depot"=>"PB","sigungu_code"=>"26530","city"=>"부산광역시 사상구","tax_code"=>"B100"],
            ["depot"=>"US","sigungu_code"=>"26710","city"=>"부산광역시 기장군","tax_code"=>"B110"],
            ["depot"=>"TG","sigungu_code"=>"27110","city"=>"대구광역시 중구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27140","city"=>"대구광역시 동구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27170","city"=>"대구광역시 서구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27200","city"=>"대구광역시 남구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27230","city"=>"대구광역시 북구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27260","city"=>"대구광역시 수성구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27290","city"=>"대구광역시 달서구","tax_code"=>"C100"],
            ["depot"=>"TG","sigungu_code"=>"27710","city"=>"대구광역시 달성군","tax_code"=>"C110"],
            ["depot"=>"TG","sigungu_code"=>"27720","city"=>"대구광역시 군위군","tax_code"=>"C100"],
            ["depot"=>"IC","sigungu_code"=>"28110","city"=>"인천광역시 중구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28140","city"=>"인천광역시 동구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28177","city"=>"인천광역시 미추홀구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28185","city"=>"인천광역시 연수구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28200","city"=>"인천광역시 남동구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28237","city"=>"인천광역시 부평구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28245","city"=>"인천광역시 계양구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28260","city"=>"인천광역시 서구","tax_code"=>"D100"],
            ["depot"=>"IC","sigungu_code"=>"28710","city"=>"인천광역시 강화군","tax_code"=>"D120"],
            ["depot"=>"IC","sigungu_code"=>"28720","city"=>"인천광역시 옹진군","tax_code"=>"D110"],
            ["depot"=>"KJ","sigungu_code"=>"29110","city"=>"광주광역시 동구","tax_code"=>"E100"],
            ["depot"=>"KJ","sigungu_code"=>"29140","city"=>"광주광역시 서구","tax_code"=>"E100"],
            ["depot"=>"KJ","sigungu_code"=>"29155","city"=>"광주광역시 남구","tax_code"=>"E100"],
            ["depot"=>"KJ","sigungu_code"=>"29170","city"=>"광주광역시 북구","tax_code"=>"E100"],
            ["depot"=>"KJ","sigungu_code"=>"29200","city"=>"광주광역시 광산구","tax_code"=>"E100"],
            ["depot"=>"TJ","sigungu_code"=>"30110","city"=>"대전광역시 동구","tax_code"=>"F100"],
            ["depot"=>"TJ","sigungu_code"=>"30140","city"=>"대전광역시 중구","tax_code"=>"F100"],
            ["depot"=>"TJ","sigungu_code"=>"30170","city"=>"대전광역시 서구","tax_code"=>"F100"],
            ["depot"=>"TJ","sigungu_code"=>"30200","city"=>"대전광역시 유성구","tax_code"=>"F100"],
            ["depot"=>"TJ","sigungu_code"=>"30230","city"=>"대전광역시 대덕구","tax_code"=>"F100"],
            ["depot"=>"US","sigungu_code"=>"31110","city"=>"울산광역시 중구","tax_code"=>"O100"],
            ["depot"=>"US","sigungu_code"=>"31140","city"=>"울산광역시 남구","tax_code"=>"O100"],
            ["depot"=>"US","sigungu_code"=>"31170","city"=>"울산광역시 동구","tax_code"=>"O100"],
            ["depot"=>"US","sigungu_code"=>"31200","city"=>"울산광역시 북구","tax_code"=>"O100"],
            ["depot"=>"US","sigungu_code"=>"31710","city"=>"울산광역시 울주군","tax_code"=>"O110"],
            ["depot"=>"CG","sigungu_code"=>"36110","city"=>"세종특별자치시","tax_code"=>"J170"],
            ["depot"=>"OS","sigungu_code"=>"41110","city"=>"경기도 수원시","tax_code"=>"G110"],
            ["depot"=>"OS","sigungu_code"=>"41111","city"=>"경기도 수원시 장안구","tax_code"=>"G110"],
            ["depot"=>"OS","sigungu_code"=>"41113","city"=>"경기도 수원시 권선구","tax_code"=>"G110"],
            ["depot"=>"OS","sigungu_code"=>"41115","city"=>"경기도 수원시 팔달구","tax_code"=>"G110"],
            ["depot"=>"OS","sigungu_code"=>"41117","city"=>"경기도 수원시 영통구","tax_code"=>"G110"],
            ["depot"=>"KN","sigungu_code"=>"41130","city"=>"경기도 성남시","tax_code"=>"G120"],
            ["depot"=>"KN","sigungu_code"=>"41131","city"=>"경기도 성남시 수정구","tax_code"=>"G120"],
            ["depot"=>"KN","sigungu_code"=>"41133","city"=>"경기도 성남시 중원구","tax_code"=>"G120"],
            ["depot"=>"KN","sigungu_code"=>"41135","city"=>"경기도 성남시 분당구","tax_code"=>"G120"],
            ["depot"=>"BB","sigungu_code"=>"41150","city"=>"경기도 의정부시","tax_code"=>"G130"],
            ["depot"=>"GP","sigungu_code"=>"41170","city"=>"경기도 안양시","tax_code"=>"G140"],
            ["depot"=>"GP","sigungu_code"=>"41171","city"=>"경기도 안양시 만안구","tax_code"=>"G140"],
            ["depot"=>"GP","sigungu_code"=>"41173","city"=>"경기도 안양시 동안구","tax_code"=>"G140"],
            ["depot"=>"IC","sigungu_code"=>"41190","city"=>"경기도 부천시","tax_code"=>"G150"],
            ["depot"=>"GP","sigungu_code"=>"41210","city"=>"경기도 광명시","tax_code"=>"G160"],
            ["depot"=>"AS","sigungu_code"=>"41220","city"=>"경기도 평택시","tax_code"=>"G220"],
            ["depot"=>"BB","sigungu_code"=>"41250","city"=>"경기도 동두천시","tax_code"=>"G170"],
            ["depot"=>"GP","sigungu_code"=>"41270","city"=>"경기도 안산시","tax_code"=>"G180"],
            ["depot"=>"GP","sigungu_code"=>"41271","city"=>"경기도 안산시 상록구","tax_code"=>"G180"],
            ["depot"=>"GP","sigungu_code"=>"41273","city"=>"경기도 안산시 단원구","tax_code"=>"G180"],
            ["depot"=>"KB","sigungu_code"=>"41280","city"=>"경기도 고양시","tax_code"=>"G190"],
            ["depot"=>"KB","sigungu_code"=>"41281","city"=>"경기도 고양시 덕양구","tax_code"=>"G190"],
            ["depot"=>"KB","sigungu_code"=>"41285","city"=>"경기도 고양시 일산동구","tax_code"=>"G190"],
            ["depot"=>"KB","sigungu_code"=>"41287","city"=>"경기도 고양시 일산서구","tax_code"=>"G190"],
            ["depot"=>"GP","sigungu_code"=>"41290","city"=>"경기도 과천시","tax_code"=>"G200"],
            ["depot"=>"KN","sigungu_code"=>"41310","city"=>"경기도 구리시","tax_code"=>"G210"],
            ["depot"=>"BB","sigungu_code"=>"41360","city"=>"경기도 남양주시","tax_code"=>"G230"],
            ["depot"=>"OS","sigungu_code"=>"41370","city"=>"경기도 오산시","tax_code"=>"G240"],
            ["depot"=>"GP","sigungu_code"=>"41390","city"=>"경기도 시흥시","tax_code"=>"G250"],
            ["depot"=>"GP","sigungu_code"=>"41410","city"=>"경기도 군포시","tax_code"=>"G260"],
            ["depot"=>"GP","sigungu_code"=>"41430","city"=>"경기도 의왕시","tax_code"=>"G270"],
            ["depot"=>"KN","sigungu_code"=>"41450","city"=>"경기도 하남시","tax_code"=>"G280"],
            ["depot"=>"OS","sigungu_code"=>"41460","city"=>"경기도 용인시","tax_code"=>"G390"],
            ["depot"=>"OS","sigungu_code"=>"41461","city"=>"경기도 용인시 처인구","tax_code"=>"G390"],
            ["depot"=>"OS","sigungu_code"=>"41463","city"=>"경기도 용인시 기흥구","tax_code"=>"G390"],
            ["depot"=>"OS","sigungu_code"=>"41465","city"=>"경기도 용인시 수지구","tax_code"=>"G390"],
            ["depot"=>"KB","sigungu_code"=>"41480","city"=>"경기도 파주시","tax_code"=>"G320"],
            ["depot"=>"OS","sigungu_code"=>"41500","city"=>"경기도 이천시","tax_code"=>"G380"],
            ["depot"=>"OS","sigungu_code"=>"41550","city"=>"경기도 안성시","tax_code"=>"G400"],
            ["depot"=>"KB","sigungu_code"=>"41570","city"=>"경기도 김포시","tax_code"=>"G410"],
            ["depot"=>"OS","sigungu_code"=>"41590","city"=>"경기도 화성시","tax_code"=>"G310"],
            ["depot"=>"OS","sigungu_code"=>"41610","city"=>"경기도 광주시","tax_code"=>"G330"],
            ["depot"=>"BB","sigungu_code"=>"41630","city"=>"경기도 양주시","tax_code"=>"G290"],
            ["depot"=>"BB","sigungu_code"=>"41650","city"=>"경기도 포천시","tax_code"=>"G350"],
            ["depot"=>"OS","sigungu_code"=>"41670","city"=>"경기도 여주시","tax_code"=>"G300"],
            ["depot"=>"BB","sigungu_code"=>"41800","city"=>"경기도 연천군","tax_code"=>"G340"],
            ["depot"=>"BB","sigungu_code"=>"41820","city"=>"경기도 가평군","tax_code"=>"G360"],
            ["depot"=>"WJ","sigungu_code"=>"41830","city"=>"경기도 양평군","tax_code"=>"G370"],
            ["depot"=>"CG","sigungu_code"=>"43110","city"=>"충청북도 청주시","tax_code"=>"I110"],
            ["depot"=>"CG","sigungu_code"=>"43111","city"=>"충청북도 청주시 상당구","tax_code"=>"I110"],
            ["depot"=>"CG","sigungu_code"=>"43112","city"=>"충청북도 청주시 서원구","tax_code"=>"I110"],
            ["depot"=>"CG","sigungu_code"=>"43113","city"=>"충청북도 청주시 흥덕구","tax_code"=>"I110"],
            ["depot"=>"CG","sigungu_code"=>"43114","city"=>"충청북도 청주시 청원구","tax_code"=>"I110"],
            ["depot"=>"CG","sigungu_code"=>"43130","city"=>"충청북도 충주시","tax_code"=>"I120"],
            ["depot"=>"WJ","sigungu_code"=>"43150","city"=>"충청북도 제천시","tax_code"=>"I130"],
            ["depot"=>"CG","sigungu_code"=>"43720","city"=>"충청북도 보은군","tax_code"=>"I150"],
            ["depot"=>"TJ","sigungu_code"=>"43730","city"=>"충청북도 옥천군","tax_code"=>"I160"],
            ["depot"=>"TJ","sigungu_code"=>"43740","city"=>"충청북도 영동군","tax_code"=>"I170"],
            ["depot"=>"CG","sigungu_code"=>"43745","city"=>"충청북도 증평군","tax_code"=>"I220"],
            ["depot"=>"CG","sigungu_code"=>"43750","city"=>"충청북도 진천군","tax_code"=>"I180"],
            ["depot"=>"CG","sigungu_code"=>"43760","city"=>"충청북도 괴산군","tax_code"=>"I190"],
            ["depot"=>"CG","sigungu_code"=>"43770","city"=>"충청북도 음성군","tax_code"=>"I200"],
            ["depot"=>"WJ","sigungu_code"=>"43800","city"=>"충청북도 단양군","tax_code"=>"I210"],
            ["depot"=>"AS","sigungu_code"=>"44130","city"=>"충청남도 천안시","tax_code"=>"J110"],
            ["depot"=>"AS","sigungu_code"=>"44131","city"=>"충청남도 천안시 동남구","tax_code"=>"J110"],
            ["depot"=>"AS","sigungu_code"=>"44133","city"=>"충청남도 천안시 서북구","tax_code"=>"J110"],
            ["depot"=>"TJ","sigungu_code"=>"44150","city"=>"충청남도 공주시","tax_code"=>"J120"],
            ["depot"=>"AS","sigungu_code"=>"44180","city"=>"충청남도 보령시","tax_code"=>"J130"],
            ["depot"=>"AS","sigungu_code"=>"44200","city"=>"충청남도 아산시","tax_code"=>"J140"],
            ["depot"=>"AS","sigungu_code"=>"44210","city"=>"충청남도 서산시","tax_code"=>"J150"],
            ["depot"=>"TJ","sigungu_code"=>"44230","city"=>"충청남도 논산시","tax_code"=>"J180"],
            ["depot"=>"TJ","sigungu_code"=>"44250","city"=>"충청남도 계룡시","tax_code"=>"J260"],
            ["depot"=>"AS","sigungu_code"=>"44270","city"=>"충청남도 당진시","tax_code"=>"J250"],
            ["depot"=>"TJ","sigungu_code"=>"44710","city"=>"충청남도 금산군","tax_code"=>"J160"],
            ["depot"=>"TJ","sigungu_code"=>"44760","city"=>"충청남도 부여군","tax_code"=>"J190"],
            ["depot"=>"TJ","sigungu_code"=>"44770","city"=>"충청남도 서천군","tax_code"=>"J200"],
            ["depot"=>"AS","sigungu_code"=>"44790","city"=>"충청남도 청양군","tax_code"=>"J210"],
            ["depot"=>"AS","sigungu_code"=>"44800","city"=>"충청남도 홍성군","tax_code"=>"J220"],
            ["depot"=>"AS","sigungu_code"=>"44810","city"=>"충청남도 예산군","tax_code"=>"J230"],
            ["depot"=>"AS","sigungu_code"=>"44825","city"=>"충청남도 태안군","tax_code"=>"J240"],
            ["depot"=>"JJ","sigungu_code"=>"45110","city"=>"전라북도 전주시","tax_code"=>"K110"],
            ["depot"=>"JJ","sigungu_code"=>"45111","city"=>"전라북도 전주시 완산구","tax_code"=>"K110"],
            ["depot"=>"JJ","sigungu_code"=>"45113","city"=>"전라북도 전주시 덕진구","tax_code"=>"K110"],
            ["depot"=>"JJ","sigungu_code"=>"45130","city"=>"전라북도 군산시","tax_code"=>"K120"],
            ["depot"=>"JJ","sigungu_code"=>"45140","city"=>"전라북도 익산시","tax_code"=>"K130"],
            ["depot"=>"JJ","sigungu_code"=>"45180","city"=>"전라북도 정읍시","tax_code"=>"K140"],
            ["depot"=>"KJ","sigungu_code"=>"45190","city"=>"전라북도 남원시","tax_code"=>"K150"],
            ["depot"=>"JJ","sigungu_code"=>"45210","city"=>"전라북도 김제시","tax_code"=>"K160"],
            ["depot"=>"JJ","sigungu_code"=>"45710","city"=>"전라북도 완주군","tax_code"=>"K170"],
            ["depot"=>"JJ","sigungu_code"=>"45720","city"=>"전라북도 진안군","tax_code"=>"K180"],
            ["depot"=>"TJ","sigungu_code"=>"45730","city"=>"전라북도 무주군","tax_code"=>"K190"],
            ["depot"=>"JJ","sigungu_code"=>"45740","city"=>"전라북도 장수군","tax_code"=>"K200"],
            ["depot"=>"JJ","sigungu_code"=>"45750","city"=>"전라북도 임실군","tax_code"=>"K210"],
            ["depot"=>"KJ","sigungu_code"=>"45770","city"=>"전라북도 순창군","tax_code"=>"K220"],
            ["depot"=>"KJ","sigungu_code"=>"45790","city"=>"전라북도 고창군","tax_code"=>"K230"],
            ["depot"=>"JJ","sigungu_code"=>"45800","city"=>"전라북도 부안군","tax_code"=>"K240"],
            ["depot"=>"KJ","sigungu_code"=>"46110","city"=>"전라남도 목포시","tax_code"=>"L110"],
            ["depot"=>"KJ","sigungu_code"=>"46130","city"=>"전라남도 여수시","tax_code"=>"L120"],
            ["depot"=>"KJ","sigungu_code"=>"46150","city"=>"전라남도 순천시","tax_code"=>"L130"],
            ["depot"=>"KJ","sigungu_code"=>"46170","city"=>"전라남도 나주시","tax_code"=>"L140"],
            ["depot"=>"KJ","sigungu_code"=>"46230","city"=>"전라남도 광양시","tax_code"=>"L160"],
            ["depot"=>"KJ","sigungu_code"=>"46710","city"=>"전라남도 담양군","tax_code"=>"L170"],
            ["depot"=>"KJ","sigungu_code"=>"46720","city"=>"전라남도 곡성군","tax_code"=>"L180"],
            ["depot"=>"KJ","sigungu_code"=>"46730","city"=>"전라남도 구례군","tax_code"=>"L190"],
            ["depot"=>"KJ","sigungu_code"=>"46770","city"=>"전라남도 고흥군","tax_code"=>"L210"],
            ["depot"=>"KJ","sigungu_code"=>"46780","city"=>"전라남도 보성군","tax_code"=>"L220"],
            ["depot"=>"KJ","sigungu_code"=>"46790","city"=>"전라남도 화순군","tax_code"=>"L230"],
            ["depot"=>"KJ","sigungu_code"=>"46800","city"=>"전라남도 장흥군","tax_code"=>"L240"],
            ["depot"=>"KJ","sigungu_code"=>"46810","city"=>"전라남도 강진군","tax_code"=>"L250"],
            ["depot"=>"KJ","sigungu_code"=>"46820","city"=>"전라남도 해남군","tax_code"=>"L260"],
            ["depot"=>"KJ","sigungu_code"=>"46830","city"=>"전라남도 영암군","tax_code"=>"L270"],
            ["depot"=>"KJ","sigungu_code"=>"46840","city"=>"전라남도 무안군","tax_code"=>"L280"],
            ["depot"=>"KJ","sigungu_code"=>"46860","city"=>"전라남도 함평군","tax_code"=>"L290"],
            ["depot"=>"KJ","sigungu_code"=>"46870","city"=>"전라남도 영광군","tax_code"=>"L300"],
            ["depot"=>"KJ","sigungu_code"=>"46880","city"=>"전라남도 장성군","tax_code"=>"L310"],
            ["depot"=>"KJ","sigungu_code"=>"46890","city"=>"전라남도 완도군","tax_code"=>"L320"],
            ["depot"=>"KJ","sigungu_code"=>"46900","city"=>"전라남도 진도군","tax_code"=>"L330"],
            ["depot"=>"KJ","sigungu_code"=>"46910","city"=>"전라남도 신안군","tax_code"=>"L340"],
            ["depot"=>"US","sigungu_code"=>"47110","city"=>"경상북도 포항시","tax_code"=>"M110"],
            ["depot"=>"US","sigungu_code"=>"47111","city"=>"경상북도 포항시 남구","tax_code"=>"M110"],
            ["depot"=>"US","sigungu_code"=>"47113","city"=>"경상북도 포항시 북구","tax_code"=>"M110"],
            ["depot"=>"US","sigungu_code"=>"47130","city"=>"경상북도 경주시","tax_code"=>"M120"],
            ["depot"=>"TG","sigungu_code"=>"47150","city"=>"경상북도 김천시","tax_code"=>"M130"],
            ["depot"=>"TG","sigungu_code"=>"47170","city"=>"경상북도 안동시","tax_code"=>"M140"],
            ["depot"=>"TG","sigungu_code"=>"47190","city"=>"경상북도 구미시","tax_code"=>"M150"],
            ["depot"=>"TG","sigungu_code"=>"47210","city"=>"경상북도 영주시","tax_code"=>"M160"],
            ["depot"=>"TG","sigungu_code"=>"47230","city"=>"경상북도 영천시","tax_code"=>"M170"],
            ["depot"=>"TG","sigungu_code"=>"47250","city"=>"경상북도 상주시","tax_code"=>"M180"],
            ["depot"=>"TG","sigungu_code"=>"47280","city"=>"경상북도 문경시","tax_code"=>"M190"],
            ["depot"=>"TG","sigungu_code"=>"47290","city"=>"경상북도 경산시","tax_code"=>"M200"],
            ["depot"=>"TG","sigungu_code"=>"47730","city"=>"경상북도 의성군","tax_code"=>"M220"],
            ["depot"=>"TG","sigungu_code"=>"47750","city"=>"경상북도 청송군","tax_code"=>"M230"],
            ["depot"=>"TG","sigungu_code"=>"47760","city"=>"경상북도 영양군","tax_code"=>"M240"],
            ["depot"=>"US","sigungu_code"=>"47770","city"=>"경상북도 영덕군","tax_code"=>"M250"],
            ["depot"=>"TG","sigungu_code"=>"47820","city"=>"경상북도 청도군","tax_code"=>"M260"],
            ["depot"=>"TG","sigungu_code"=>"47830","city"=>"경상북도 고령군","tax_code"=>"M270"],
            ["depot"=>"TG","sigungu_code"=>"47840","city"=>"경상북도 성주군","tax_code"=>"M280"],
            ["depot"=>"TG","sigungu_code"=>"47850","city"=>"경상북도 칠곡군","tax_code"=>"M290"],
            ["depot"=>"TG","sigungu_code"=>"47900","city"=>"경상북도 예천군","tax_code"=>"M300"],
            ["depot"=>"TG","sigungu_code"=>"47920","city"=>"경상북도 봉화군","tax_code"=>"M310"],
            ["depot"=>"TG","sigungu_code"=>"47930","city"=>"경상북도 울진군","tax_code"=>"M320"],
            ["depot"=>"US","sigungu_code"=>"47940","city"=>"경상북도 울릉군","tax_code"=>"M330"],
            ["depot"=>"MS","sigungu_code"=>"48120","city"=>"경상남도 창원시","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48121","city"=>"경상남도 창원시 의창구","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48123","city"=>"경상남도 창원시 성산구","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48125","city"=>"경상남도 창원시 마산합포구","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48127","city"=>"경상남도 창원시 마산회원구","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48129","city"=>"경상남도 창원시 진해구","tax_code"=>"N110"],
            ["depot"=>"MS","sigungu_code"=>"48170","city"=>"경상남도 진주시","tax_code"=>"N140"],
            ["depot"=>"MS","sigungu_code"=>"48220","city"=>"경상남도 통영시","tax_code"=>"N160"],
            ["depot"=>"MS","sigungu_code"=>"48240","city"=>"경상남도 사천시","tax_code"=>"N170"],
            ["depot"=>"MS","sigungu_code"=>"48250","city"=>"경상남도 김해시","tax_code"=>"N180"],
            ["depot"=>"MS","sigungu_code"=>"48270","city"=>"경상남도 밀양시","tax_code"=>"N190"],
            ["depot"=>"MS","sigungu_code"=>"48310","city"=>"경상남도 거제시","tax_code"=>"N200"],
            ["depot"=>"PB","sigungu_code"=>"48330","city"=>"경상남도 양산시","tax_code"=>"N240"],
            ["depot"=>"MS","sigungu_code"=>"48720","city"=>"경상남도 의령군","tax_code"=>"N210"],
            ["depot"=>"MS","sigungu_code"=>"48730","city"=>"경상남도 함안군","tax_code"=>"N220"],
            ["depot"=>"MS","sigungu_code"=>"48740","city"=>"경상남도 창녕군","tax_code"=>"N230"],
            ["depot"=>"MS","sigungu_code"=>"48820","city"=>"경상남도 고성군","tax_code"=>"N250"],
            ["depot"=>"KJ","sigungu_code"=>"48840","city"=>"경상남도 남해군","tax_code"=>"N260"],
            ["depot"=>"KJ","sigungu_code"=>"48850","city"=>"경상남도 하동군","tax_code"=>"N270"],
            ["depot"=>"MS","sigungu_code"=>"48860","city"=>"경상남도 산청군","tax_code"=>"N280"],
            ["depot"=>"MS","sigungu_code"=>"48870","city"=>"경상남도 함양군","tax_code"=>"N290"],
            ["depot"=>"TG","sigungu_code"=>"48880","city"=>"경상남도 거창군","tax_code"=>"N300"],
            ["depot"=>"TG","sigungu_code"=>"48890","city"=>"경상남도 합천군","tax_code"=>"N310"],
            ["depot"=>"CJ","sigungu_code"=>"50110","city"=>"제주특별자치도 제주시","tax_code"=>"Z110"],
            ["depot"=>"CJ","sigungu_code"=>"50130","city"=>"제주특별자치도 서귀포시","tax_code"=>"Z120"],
            ["depot"=>"WJ","sigungu_code"=>"51110","city"=>"강원특별자치도 춘천시","tax_code"=>"H110"],
            ["depot"=>"WJ","sigungu_code"=>"51130","city"=>"강원특별자치도 원주시","tax_code"=>"H120"],
            ["depot"=>"KL","sigungu_code"=>"51150","city"=>"강원특별자치도 강릉시","tax_code"=>"H130"],
            ["depot"=>"KL","sigungu_code"=>"51170","city"=>"강원특별자치도 동해시","tax_code"=>"H140"],
            ["depot"=>"KL","sigungu_code"=>"51190","city"=>"강원특별자치도 태백시","tax_code"=>"H150"],
            ["depot"=>"KL","sigungu_code"=>"51210","city"=>"강원특별자치도 속초시","tax_code"=>"H160"],
            ["depot"=>"KL","sigungu_code"=>"51230","city"=>"강원특별자치도 삼척시","tax_code"=>"H170"],
            ["depot"=>"WJ","sigungu_code"=>"51720","city"=>"강원특별자치도 홍천군","tax_code"=>"H180"],
            ["depot"=>"WJ","sigungu_code"=>"51730","city"=>"강원특별자치도 횡성군","tax_code"=>"H190"],
            ["depot"=>"WJ","sigungu_code"=>"51750","city"=>"강원특별자치도 영월군","tax_code"=>"H200"],
            ["depot"=>"WJ","sigungu_code"=>"51760","city"=>"강원특별자치도 평창군","tax_code"=>"H210"],
            ["depot"=>"KL","sigungu_code"=>"51770","city"=>"강원특별자치도 정선군","tax_code"=>"H220"],
            ["depot"=>"BB","sigungu_code"=>"51780","city"=>"강원특별자치도 철원군","tax_code"=>"H230"],
            ["depot"=>"WJ","sigungu_code"=>"51790","city"=>"강원특별자치도 화천군","tax_code"=>"H240"],
            ["depot"=>"WJ","sigungu_code"=>"51800","city"=>"강원특별자치도 양구군","tax_code"=>"H250"],
            ["depot"=>"KL","sigungu_code"=>"51810","city"=>"강원특별자치도 인제군","tax_code"=>"H260"],
            ["depot"=>"KL","sigungu_code"=>"51820","city"=>"강원특별자치도 고성군","tax_code"=>"H270"],
            ["depot"=>"KL","sigungu_code"=>"51830","city"=>"강원특별자치도 양양군","tax_code"=>"H280"]
        ];

        $this->moduleDataSetup->startSetup();

        foreach ($sigunguCodes as $sigunguCode) {
            $this->createSigunguCode()->setData($sigunguCode)->save();
        }
        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Create sigungu code model instance
     *
     * @return mixed
     */
    private function createSigunguCode()
    {
        return $this->sigunguCodeFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '0.0.1';
    }
}
