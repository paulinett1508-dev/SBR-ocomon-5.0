<?php 

if (is_file('../config.inc.php'))
	require_once ('../config.inc.php'); 
elseif (is_file('./includes/config.inc.php'))
	include_once ('./includes/config.inc.php');
elseif (is_file('../../includes/config.inc.php'))
	include_once ('../../includes/config.inc.php');

$QRY["total_equip"] = "SELECT count(*) total from equipamentos";



$QRY["full_detail_ini"] = "SELECT 
			c.comp_cod, c.comp_inv as etiqueta, c.comp_sn as serial, c.comp_part_number as part_number,
			c.comp_nome as nome, 
 			c.comp_nf as nota, inst.inst_nome as instituicao, inst.inst_cod as cod_inst, 

 			c.comp_coment as comentario, c.comp_valor as valor, c.comp_data as data_cadastro, 
			c.comp_data_compra as data_compra, c.comp_ccusto as ccusto, c.comp_situac as situacao, 
			c.comp_local as tipo_local, loc.loc_reitoria as reitoria_cod, reit.reit_nome as reitoria, 
			c.comp_mb as tipo_mb, c.comp_proc as tipo_proc, 
			c.comp_tipo_equip as tipo, c.comp_memo as tipo_memo, c.comp_video as tipo_video, 
			c.comp_modelohd as tipo_hd, c.comp_modem as tipo_modem, c.comp_cdrom as tipo_cdrom, 
			c.comp_dvd as tipo_dvd, c.comp_grav as tipo_grav, c.comp_resolucao as tipo_resol, 
			c.comp_polegada as tipo_pole, c.comp_tipo_imp as tipo_imp, c.comp_assist as assistencia_cod, 
			equip.tipo_nome as equipamento, equip.tipo_categoria as categoria_cod, c.comp_rede as tipo_rede, c.comp_som as tipo_som, 
			t.tipo_imp_nome as impressora, 

			cl.nickname,
			
			loc.local, 

			proc.mdit_fabricante as fabricante_proc, proc.mdit_desc as processador, 
			proc.mdit_desc_capacidade as clock, proc.mdit_cod as cod_processador, 
			proc.mdit_sufixo as proc_sufixo, 
			hd.mdit_fabricante as fabricante_hd, hd.mdit_desc as hd, hd.mdit_desc_capacidade as hd_capacidade, 
			hd.mdit_cod as cod_hd, 
			hd.mdit_sufixo as hd_sufixo, 
			vid.mdit_fabricante as fabricante_video, vid.mdit_desc as video, vid.mdit_cod as cod_video, 
			red.mdit_fabricante as rede_fabricante, red.mdit_desc as rede, red.mdit_cod as cod_rede, 
			md.mdit_fabricante as fabricante_modem, md.mdit_desc as modem, md.mdit_cod as cod_modem, 
			cd.mdit_fabricante as fabricante_cdrom, cd.mdit_desc as cdrom, cd.mdit_cod as cod_cdrom, 
			grav.mdit_fabricante as fabricante_gravador, grav.mdit_desc as gravador, grav.mdit_cod as cod_gravador, 
			dvd.mdit_fabricante as fabricante_dvd, dvd.mdit_desc as dvd, dvd.mdit_cod as cod_dvd, 
			mb.mdit_fabricante as fabricante_mb, mb.mdit_desc as mb, mb.mdit_cod as cod_mb, 
			memo.mdit_desc_capacidade as memoria, memo.mdit_cod as cod_memoria, memo.mdit_sufixo as memo_sufixo, 
			som.mdit_fabricante as fabricante_som, som.mdit_desc as som, som.mdit_cod as cod_som, 

			fab.fab_nome as fab_nome, fab.fab_cod as fab_cod, fo.forn_cod as fornecedor_cod, 
			fo.forn_nome as fornecedor_nome, model.marc_cod as modelo_cod, model.marc_nome as modelo, 
			pol.pole_cod as polegada_cod, pol.pole_nome as polegada_nome, 
			res.resol_cod as resolucao_cod, res.resol_nome as resol_nome, 
			sit.situac_cod as situac_cod, sit.situac_nome as situac_nome, sit.situac_destaque as situac_destaque, 

			tmp.tempo_meses as tempo, tmp.tempo_cod as tempo_cod, 
			tp.tipo_garant_nome as tipo_garantia, tp.tipo_garant_cod as garantia_cod, 

			date_add(c.comp_data_compra, interval tmp.tempo_meses month)as vencimento, 
			assist.assist_desc as assistencia 

		FROM (((((((((((((((((((((((((
			equipamentos c,
			marcas_comp as model, 
			tipo_equip as equip, 
			instituicao as inst
			LEFT JOIN clients cl ON cl.id = inst.inst_client)
			
			left join  tipo_imp as t on t.tipo_imp_cod = c.comp_tipo_imp) 

			left join polegada as pol on c.comp_polegada = pol.pole_cod) 
			left join resolucao as res on c.comp_resolucao = res.resol_cod) 
			left join fabricantes as fab on fab.fab_cod = c.comp_fab) 
			left join fornecedores as fo on fo.forn_cod = c.comp_fornecedor) 
			left join situacao as sit on sit.situac_cod = c.comp_situac) 
			left join tempo_garantia as tmp on tmp.tempo_cod =c.comp_garant_meses) 
			left join tipo_garantia as tp on tp.tipo_garant_cod = c.comp_tipo_garant) 

			left join assistencia as assist on assist.assist_cod = c.comp_assist) 


			left join modelos_itens as proc on proc.mdit_cod = c.comp_proc) 
			left join modelos_itens as hd on hd.mdit_cod = c.comp_modelohd) 
			left join modelos_itens as vid on vid.mdit_cod = c.comp_video) 
			left join modelos_itens as red on red.mdit_cod = c.comp_rede) 
			left join modelos_itens as md on md.mdit_cod = c.comp_modem) 
			left join modelos_itens as cd on cd.mdit_cod = c.comp_cdrom) 
			left join modelos_itens as grav on grav.mdit_cod = c.comp_grav) 
			left join modelos_itens as dvd on dvd.mdit_cod = c.comp_dvd) 
			left join modelos_itens as mb on mb.mdit_cod = c.comp_mb) 
			left join modelos_itens as memo on memo.mdit_cod = c.comp_memo) 
			left join modelos_itens as som on som.mdit_cod = c.comp_som) 

			left join hw_sw as hw on hw.hws_hw_cod = c.comp_inv and hw.hws_hw_inst = c.comp_inst) 
			left join softwares as soft on soft.soft_cod = hw.hws_sw_cod) 

			left join localizacao as loc on loc.loc_id = c.comp_local) 
			left join reitorias as reit on reit.reit_cod = loc.loc_id)
			 
        WHERE 
			(c.comp_inst = inst.inst_cod) and (c.comp_marca = model.marc_cod) and 
			(c.comp_tipo_equip = equip.tipo_cod) ";

// $QRY["full_detail_fim"] = "\nGROUP BY comp_inv, comp_inst";
$QRY["full_detail_fim"] = "
GROUP BY 
	etiqueta, instituicao, 
	c.comp_cod, serial, part_number, nome, nota, cod_inst, comentario, valor, data_cadastro, data_compra, ccusto, situacao, tipo_local, tipo_categoria, cl.nickname,
	reitoria_cod, reitoria, tipo_mb, tipo_proc, tipo, tipo_memo, tipo_video, tipo_hd, tipo_modem, tipo_cdrom, 
	tipo_dvd, tipo_grav, tipo_resol, tipo_pole, tipo_imp, assistencia_cod, equipamento, tipo_rede, tipo_som, impressora, loc.local, fabricante_proc, processador, clock, cod_processador, proc_sufixo, fabricante_hd, hd, hd_capacidade, cod_hd, hd_sufixo, fabricante_video, video, cod_video, rede_fabricante,  rede, cod_rede, fabricante_modem, modem, cod_modem, fabricante_cdrom, cdrom, cod_cdrom, fabricante_gravador, gravador, cod_gravador, fabricante_dvd, dvd, cod_dvd, fabricante_mb, mb, cod_mb, memoria, cod_memoria, memo_sufixo, fabricante_som, som, cod_som, fab_nome, fab_cod, fornecedor_cod, fornecedor_nome, modelo_cod, modelo, polegada_cod, polegada_nome, resolucao_cod, resol_nome, situac_cod, situac_nome, situac_destaque, tempo, tempo_cod, tipo_garantia, garantia_cod, vencimento, assistencia
"; //software, versao,




$QRY["configuration_models"] = "
		SELECT 
			mold.mold_cod as cod,
			mold.mold_marca AS padrao, mold.mold_inv AS etiqueta, mold.mold_sn AS serial, mold.mold_nome AS nome, 
			mold.mold_nf AS nota, mold.mold_coment AS comentario, mold.mold_valor AS valor, mold.mold_data_compra AS 
			data_compra, mold.mold_ccusto AS ccusto, 
			inst.inst_nome AS instituicao, inst.inst_cod AS cod_inst, 
			equip.tipo_nome AS equipamento, equip.tipo_cod AS equipamento_cod, 
			t.tipo_imp_nome AS impressora, t.tipo_imp_cod AS impressora_cod, 
			loc.local AS local, loc.loc_id AS local_cod, 
			proc.mdit_fabricante AS fabricante_proc, proc.mdit_desc AS processador, proc.mdit_desc_capacidade AS clock, 
			proc.mdit_cod AS cod_processador, hd.mdit_fabricante AS fabricante_hd, hd.mdit_desc AS hd, 
			hd.mdit_desc_capacidade AS hd_capacidade,hd.mdit_cod AS cod_hd, 
			vid.mdit_fabricante AS fabricante_video, vid.mdit_desc AS video, vid.mdit_cod AS cod_video, 
			red.mdit_fabricante AS rede_fabricante, red.mdit_desc AS rede, red.mdit_cod AS cod_rede, 
			md.mdit_fabricante AS fabricante_modem, md.mdit_desc AS modem, md.mdit_cod AS cod_modem, 
			cd.mdit_fabricante AS fabricante_cdrom, cd.mdit_desc AS cdrom, cd.mdit_cod AS cod_cdrom, 
			grav.mdit_fabricante AS fabricante_gravador, grav.mdit_desc AS gravador, grav.mdit_cod AS cod_gravador, 
			dvd.mdit_fabricante AS fabricante_dvd, dvd.mdit_desc AS dvd, dvd.mdit_cod AS cod_dvd, 
			mb.mdit_fabricante AS fabricante_mb, mb.mdit_desc AS mb, mb.mdit_cod AS cod_mb, 
			memo.mdit_desc AS memoria, memo.mdit_cod AS cod_memoria, 
			som.mdit_fabricante AS fabricante_som, som.mdit_desc AS som, som.mdit_cod AS cod_som, 
			fab.fab_nome AS fab_nome, fab.fab_cod AS fab_cod, 
			fo.forn_cod AS fornecedor_cod, fo.forn_nome AS fornecedor_nome, 
			model.marc_cod AS modelo_cod, model.marc_nome AS modelo, 
			pol.pole_cod AS polegada_cod, pol.pole_nome AS polegada_nome, 
			res.resol_cod AS resolucao_cod, res.resol_nome AS resol_nome 
		FROM ((((((((((((((((((moldes AS mold 
			LEFT JOIN  tipo_imp AS t ON	t.tipo_imp_cod = mold.mold_tipo_imp) 
			LEFT JOIN polegada AS pol ON mold.mold_polegada = pol.pole_cod) 
			LEFT JOIN resolucao AS res ON mold.mold_resolucao = res.resol_cod) 
			LEFT JOIN fabricantes AS fab ON fab.fab_cod = mold.mold_fab) 
			LEFT JOIN fornecedores AS fo ON fo.forn_cod = mold.mold_fornecedor) 

			LEFT JOIN modelos_itens AS proc ON proc.mdit_cod = mold.mold_proc) 
			LEFT JOIN modelos_itens AS hd ON hd.mdit_cod = mold.mold_modelohd) 
			LEFT JOIN modelos_itens AS vid ON vid.mdit_cod = mold.mold_video) 
			LEFT JOIN modelos_itens AS red ON red.mdit_cod = mold.mold_rede) 
			LEFT JOIN modelos_itens AS md ON md.mdit_cod = mold.mold_modem) 
			LEFT JOIN modelos_itens AS cd ON cd.mdit_cod = mold.mold_cdrom) 
			LEFT JOIN modelos_itens AS grav ON grav.mdit_cod = mold.mold_grav) 
			LEFT JOIN modelos_itens AS dvd ON dvd.mdit_cod = mold.mold_dvd) 
			LEFT JOIN modelos_itens AS mb ON mb.mdit_cod = mold.mold_mb) 
			LEFT JOIN modelos_itens AS memo ON memo.mdit_cod = mold.mold_memo) 
			LEFT JOIN modelos_itens AS som ON som.mdit_cod = mold.mold_som) 

			LEFT JOIN instituicao AS inst ON inst.inst_cod = mold.mold_inst) 
			LEFT JOIN localizacao AS loc ON loc.loc_id = mold.mold_local), 
			marcas_comp AS model, tipo_equip AS equip 
		WHERE 
			(mold.mold_tipo_equip = equip.tipo_cod) AND 
			(mold.mold_marca = model.marc_cod)
";







$QRY["componenteXequip_ini"] =  "SELECT ".
				"e.estoq_cod, e.estoq_tipo, e.estoq_desc, e.estoq_sn, e.estoq_comentario, e.estoq_tag_inv, e.estoq_tag_inst, ".
				"e.estoq_nf, e.estoq_warranty, e.estoq_value, e.estoq_data_compra, e.estoq_partnumber,  ".
				"i.item_nome,  ".
				"f.forn_nome, f.forn_cod, ".
				"t.tempo_meses, t.tempo_cod, ".
				"c.descricao as ccusto, c.codigo,  ".
				"m.mdit_fabricante as fabricante, m.mdit_desc as modelo, m.mdit_desc_capacidade as capacidade, m.mdit_sufixo as sufixo, fab.fab_nome, ".
				"l.local, l.loc_id, ".
				"inst.inst_nome, ".
				"s.situac_nome, s.situac_cod, situac_destaque, ".
				"eqp.eqp_equip_inv, eqp.eqp_equip_inst, ".
				"instEquip.inst_nome as instEquipamento, ".
				"assist.assist_desc as assistencia, assist.assist_cod as assistencia_cod, ".
				"tg.tipo_garant_nome as tipo_garantia, tg.tipo_garant_cod as garantia_cod ".
			"FROM ".
				"estoque e ".
				"left join instituicao as inst on inst.inst_cod = e.estoq_tag_inst ".
				"left join equipXpieces as eqp on eqp.eqp_piece_id = e.estoq_cod ".
				"left join instituicao as instEquip on instEquip.inst_cod = eqp.eqp_equip_inst ".
				"left join fornecedores as f on f.forn_cod = e.estoq_vendor ".
				"left join tempo_garantia as t on t.tempo_cod = e.estoq_warranty ".
				"left join " . TB_CCUSTO . " as c on c.codigo = e.estoq_ccusto ".
				"left join situacao as s on s.situac_cod = e.estoq_situac ".
				"left join assistencia as assist on assist.assist_cod = e.estoq_assist ".
				"left join tipo_garantia as tg on tg.tipo_garant_cod = e.estoq_warranty_type, ".
				"modelos_itens m left join fabricantes fab on m.mdit_manufacturer = fab.fab_cod, ".
				"itens i, localizacao l ".
			"WHERE ".
				"e.estoq_tipo = i.item_cod ".
				"and e.estoq_tipo = m.mdit_tipo ".
				"and e.estoq_desc = m.mdit_cod ".
				"and e.estoq_local = l.loc_id ";


$QRY["componentexequip_ini"] =  "SELECT ".
				"e.estoq_cod, e.estoq_tipo, e.estoq_desc, e.estoq_sn, e.estoq_comentario, e.estoq_tag_inv, e.estoq_tag_inst, ".
				"e.estoq_nf, e.estoq_warranty, e.estoq_value, e.estoq_data_compra, e.estoq_partnumber,  ".
				"i.item_nome,  ".
				"f.forn_nome, f.forn_cod, ".
				"t.tempo_meses, t.tempo_cod, ".
				"c.descricao as ccusto, c.codigo,  ".
				"m.mdit_fabricante as fabricante, m.mdit_manufacturer as manufacturer, m.mdit_desc as modelo, m.mdit_desc_capacidade as capacidade, m.mdit_sufixo as sufixo, fab.fab_nome, ".
				"l.local, l.loc_id, ".
				"inst.inst_nome, inst.inst_cod, ".
				"s.situac_nome, s.situac_cod, situac_destaque, ".
				"eqp.eqp_equip_inv, eqp.eqp_equip_inst, ".
				"instEquip.inst_nome as instEquipamento, ".
				"assist.assist_desc as assistencia, assist.assist_cod as assistencia_cod, ".
				"tg.tipo_garant_nome as tipo_garantia, tg.tipo_garant_cod as garantia_cod ".
			"FROM ".
				"estoque e ".
				"left join instituicao as inst on inst.inst_cod = e.estoq_tag_inst ".
				"left join equipxpieces as eqp on eqp.eqp_piece_id = e.estoq_cod ".
				"left join instituicao as instEquip on instEquip.inst_cod = eqp.eqp_equip_inst ".
				"left join fornecedores as f on f.forn_cod = e.estoq_vendor ".
				"left join tempo_garantia as t on t.tempo_cod = e.estoq_warranty ".
				"left join " . TB_CCUSTO . " as c on c.codigo = e.estoq_ccusto ".
				"left join situacao as s on s.situac_cod = e.estoq_situac ".
				"left join assistencia as assist on assist.assist_cod = e.estoq_assist ".
				"left join tipo_garantia as tg on tg.tipo_garant_cod = e.estoq_warranty_type, ".
				"modelos_itens m left join fabricantes fab on m.mdit_manufacturer = fab.fab_cod, ".
				"itens i, localizacao l ".
			"WHERE ".
				"e.estoq_tipo = i.item_cod ".
				"and e.estoq_tipo = m.mdit_tipo ".
				"and e.estoq_desc = m.mdit_cod ".
				"and e.estoq_local = l.loc_id ";


$QRY["componenteXequip_fim"] = " ORDER BY i.item_nome, e.estoq_desc";


$QRY["garantia"] = "SELECT c.comp_inv as inventario, i.inst_nome as instituicao,
			i.inst_cod as instituicao_cod,
			c.comp_data_compra as aquisicao,
			ti.tipo_garant_nome as garantia,  t.tempo_meses as meses, date_add(comp_data_compra, interval tempo_meses month)
			as vencimento,
			extract(day from date_add(comp_data_compra,
			interval tempo_meses month)) as dia,
			extract(month from date_add(comp_data_compra,
			interval tempo_meses month)) as mes,
			extract(year from date_add(comp_data_compra,
			interval tempo_meses month)) as ano,
			f.forn_nome as fornecedor,
			f.forn_fone as contato
		FROM equipamentos as c
			left join tempo_garantia as t on c.comp_garant_meses = t.tempo_cod
			left join tipo_garantia as ti on ti.tipo_garant_cod = c.comp_tipo_garant
			left join fornecedores as f on f.forn_cod = c.comp_fornecedor,
			instituicao as i
		WHERE c.comp_garant_meses is not null and
			c.comp_data_compra IS NOT NULL  AND
			c.comp_inst=i.inst_cod ";

$QRY["garantia_pieces"] = "SELECT

			e.estoq_cod, e.estoq_partnumber,
			e.estoq_data_compra as aquisicao,
			t.tempo_meses as meses, date_add(e.estoq_data_compra, interval tempo_meses month)
			as vencimento,
			extract(day from date_add(e.estoq_data_compra,
			interval tempo_meses month)) as dia,
			extract(month from date_add(e.estoq_data_compra,
			interval tempo_meses month)) as mes,
			extract(year from date_add(e.estoq_data_compra,
			interval tempo_meses month)) as ano,
			f.forn_nome as fornecedor,
			f.forn_fone as contato
		FROM estoque as e
			left join tempo_garantia as t on e.estoq_warranty = t.tempo_cod
			left join fornecedores as f on f.forn_cod = e.estoq_vendor
		WHERE e.estoq_warranty is not null and
			e.estoq_data_compra IS NOT NULL";

$QRY["garantia_pieces_OK"] = "SELECT

			e.estoq_cod, e.estoq_partnumber,
			e.estoq_data_compra as aquisicao,
			t.tempo_meses as meses, date_add(estoq_data_compra, interval tempo_meses month)
			as vencimento,
			extract(day from date_add(estoq_data_compra,
			interval tempo_meses month)) as dia,
			extract(month from date_add(estoq_data_compra,
			interval tempo_meses month)) as mes,
			extract(year from date_add(estoq_data_compra,
			interval tempo_meses month)) as ano,
			f.forn_nome as fornecedor,
			f.forn_fone as contato
		FROM estoque as e
			left join tempo_garantia as t on e.estoq_warranty = t.tempo_cod
			left join fornecedores as f on f.forn_cod = e.estoq_vendor
		WHERE e.estoq_warranty is not null and
			e.estoq_data_compra IS NOT NULL";

// monitores não inclusos
$QRY["vencimentos"] = "SELECT count(*) AS quantidade,
                 date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) AS vencimento,
                 marc_nome AS modelo, fab_nome AS fabricante, tipo_nome AS tipo
		FROM equipamentos, tempo_garantia, marcas_comp, fabricantes, tipo_equip
		WHERE date_add(comp_data_compra, INTERVAL tempo_meses MONTH) >= curdate()
                AND comp_garant_meses = tempo_cod AND comp_tipo_equip NOT IN (5)
                AND comp_marca = marc_cod AND comp_fab = fab_cod AND comp_tipo_equip = tipo_cod
                AND (date_format(curdate() , '%Y') = date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y')
                OR date_format(curdate() , '%Y' )+3>= date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y' ))
		GROUP BY vencimento, modelo
		ORDER BY vencimento, modelo";

$QRY["vencimentos_full"] = "SELECT count(*) AS quantidade,
                 date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) AS vencimento,
                 marc_nome AS modelo, fab_nome AS fabricante, tipo_nome AS tipo
		FROM equipamentos, tempo_garantia, marcas_comp, fabricantes, tipo_equip
		WHERE
		 comp_garant_meses = tempo_cod 
                AND comp_marca = marc_cod AND comp_fab = fab_cod AND comp_tipo_equip = tipo_cod
                AND (date_format(curdate() , '%Y') = date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y')
                OR date_format(curdate() , '%Y' )+5>= date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y' ))
		GROUP BY vencimento, modelo
		ORDER BY vencimento, modelo";

$QRY["vencimentos_full_ini"] = "SELECT count(*) AS quantidade,
                 date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) AS vencimento,
                 marc_nome AS modelo, fab_nome AS fabricante, tipo_nome AS tipo
		FROM equipamentos, tempo_garantia, marcas_comp, fabricantes, tipo_equip
		WHERE
		 comp_garant_meses = tempo_cod 
                AND comp_marca = marc_cod AND comp_fab = fab_cod AND comp_tipo_equip = tipo_cod
                AND (date_format(curdate() , '%Y') = date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y')
                OR date_format(curdate() , '%Y' )+5>= date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y' ))
		";
		
		/* GROUP BY vencimento, modelo
		ORDER BY vencimento, modelo"; */


$QRY["vencimentos_list_ini"] = "SELECT comp_inv as etiqueta, comp_inst,
		date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) AS vencimento,
		marc_nome AS modelo, fab_nome AS fabricante, tipo_nome AS tipo
		FROM equipamentos, tempo_garantia, marcas_comp, fabricantes, tipo_equip
		WHERE
		comp_garant_meses = tempo_cod 
		AND comp_marca = marc_cod AND comp_fab = fab_cod AND comp_tipo_equip = tipo_cod
		AND (date_format(curdate() , '%Y') = date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y')
		OR date_format(curdate() , '%Y' ) +5 >= date_format(date_add(comp_data_compra, INTERVAL tempo_meses MONTH) , '%Y' ))
		";
	//AND	date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) = '2008-02-13'
$QRY["vencimentos_list_fim"] = "ORDER BY vencimento, etiqueta, modelo";

$QRY["vencimentos_piecesOK"] = "SELECT count(*) AS quantidade, ".
                "\n\ti.item_nome AS tipo, model.mdit_fabricante as fabricante, model.mdit_desc as modelo, ".
                "\n\tmodel.mdit_desc_capacidade as capacidade, model.mdit_sufixo as sufixo, ".

                "\n\tew.ew_sent_first_alert as first_alert, ew.ew_sent_last_alert as last_alert,".

                "\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d') , INTERVAL t.tempo_meses MONTH) AS vencimento ".

		"\n\tFROM  ".
		"\n\testoque e  ".
		"\n\tleft join email_warranty ew on e.estoq_cod = ew.ew_piece_id,  ".

		"\n\ttempo_garantia t, modelos_itens model, itens i ".
		"\n\tWHERE  ".

		"\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) >= ".
		"\n\tdate_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 0 DAY) ".

		"\n\tAND ".

		"\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) <= ".
		"\n\tdate_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 30 DAY) ".


                "\n\tAND e.estoq_warranty = t.tempo_cod AND e.estoq_tipo = i.item_cod ".
                "\n\tAND e.estoq_desc = model.mdit_cod ".


		"\n\tGROUP BY vencimento, modelo ".
		"\n\tORDER BY vencimento, modelo";


$QRY["vencimentos_pieces"] = "SELECT e.estoq_cod, e.estoq_sn, e.estoq_partnumber, ".
				"\n\ti.item_nome AS tipo, model.mdit_fabricante as fabricante, model.mdit_desc as modelo, ".
				"\n\tmodel.mdit_desc_capacidade as capacidade, model.mdit_sufixo as sufixo, ".

				"\n\tew.ew_sent_first_alert as first_alert, ew.ew_sent_last_alert as last_alert,".

				"\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d') , INTERVAL t.tempo_meses MONTH) AS vencimento ".

				"\nFROM  ".
				"\n\testoque e  ".
				"\n\tleft join email_warranty ew on e.estoq_cod = ew.ew_piece_id,  ".

				"\n\ttempo_garantia t, modelos_itens model, itens i ".
				"\nWHERE  ".

				"\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) >= ".
				"\n\tdate_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 0 DAY) ".

				"\n\tAND ".

				"\n\tdate_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) <= ".
				"\n\tdate_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 30 DAY) ".


				"\n\tAND e.estoq_warranty = t.tempo_cod AND e.estoq_tipo = i.item_cod ".
				"\n\tAND e.estoq_desc = model.mdit_cod ".

				"\n\t AND ((ew.ew_sent_first_alert is null OR ew.ew_sent_first_alert=0)".

				"\n\t OR (ew.ew_sent_last_alert is null OR ew.ew_sent_last_alert=0))".

				"\nORDER BY vencimento, modelo";







$QRY["ocorrencias_full_ini_before_extended"] = "SELECT
				o.numero as numero, o.problema as prob_cod, o.descricao as descricao, o.equipamento as etiqueta,
				o.sistema as area_cod, o.contato as contato, o.telefone as telefone, o.contato_email as contato_email, 
				o.local as setor_cod,
				o.operador as operador_cod, o.data_abertura as data_abertura, o.data_fechamento as data_fechamento,
				o.status as status_cod, o.data_atendimento as data_atendimento, o.instituicao as unidade_cod,
				o.aberto_por as aberto_por_cod, o.oco_scheduled, o.oco_scheduled_to, o.oco_real_open_date, o.date_first_queued, 

				o.oco_script_sol, o.oco_prior, o.oco_channel, o.oco_tag, 

				cl.id as client_id, cl.fullname, cl.nickname, 

				i.inst_nome as unidade,

				p.problema as problema, p.prob_area as prob_area_cod, p.prob_sla as sla_solucao_cod,

				a.sistema as area, a.sis_email as area_email, a.sis_atende as sis_atende,

				asol.sistema as area_solicitante, 

				l.local as setor, l.loc_reitoria as reitoria_cod, l.loc_prior as loc_prior_cod, l.loc_dominio as dominio_cod,
				l.loc_predio as predio_cod,

				pr.prior_nivel as prioridade_nivel, pr.prior_sla as sla_resposta_cod,

				u.login as login, u.nome as nome, u.email as user_email, u.AREA as user_area, u.user_admin as user_admin,

				ua.nome as aberto_por, ua.nivel as aberto_por_nivel_cod, ua.email as aberto_por_email, ua.AREA as aberto_por_area, 

				s.status as chamado_status, s.stat_cat as stat_cat_cod, s.stat_painel as stat_painel_cod, s.stat_time_freeze as time_freeze, s.stat_ignored, 

				stc.stc_desc as status_cat,

				sls.slas_desc as sla_solucao, sls.slas_tempo as sla_solucao_tempo,

				slr.slas_desc as sla_resposta, slr.slas_tempo as sla_resposta_tempo,

				sol.script_desc, prioridade_atendimento.pr_nivel as pr_atendimento, prioridade_atendimento.pr_desc as pr_descricao, 
				prioridade_atendimento.pr_color as cor, prioridade_atendimento.pr_font_color as cor_fonte

			FROM
				ocorrencias as o left join clients cl on o.client = cl.id 
				left join sistemas as a on a.sis_id = o.sistema
				left join localizacao as l on l.loc_id = o.local
				left join instituicao as i on i.inst_cod = o.instituicao
				left join usuarios as u on u.user_id = o.operador
				left join usuarios as ua on ua.user_id = o.aberto_por

				left join sistemas as asol on asol.sis_id = ua.AREA

				left join `status` as s on s.stat_id = o.status
				left join status_categ as stc on stc.stc_cod = s.stat_cat
				left join problemas as p on p.prob_id = o.problema
				left join sla_solucao as sls on sls.slas_cod = p.prob_sla
				left join prioridades as pr on pr.prior_cod = l.loc_prior
				left join sla_solucao as slr on slr.slas_cod = pr.prior_sla

				left join script_solution as sol on sol.script_cod = o.oco_script_sol
				
				left join prior_atend as prioridade_atendimento on prioridade_atendimento.pr_cod = o.oco_prior
				";


/* Consulta acrescentando a tabela tickets_extended */
$QRY["ocorrencias_full_ini"] = "SELECT
				o.numero as numero, o.problema as prob_cod, o.descricao as descricao, o.equipamento as etiqueta,
				o.sistema as area_cod, o.contato as contato, o.telefone as telefone, o.contato_email as contato_email, 
				o.local as setor_cod,
				o.operador as operador_cod, o.data_abertura as data_abertura, o.data_fechamento as data_fechamento,
				o.status as status_cod, o.data_atendimento as data_atendimento, o.instituicao as unidade_cod,
				o.aberto_por as aberto_por_cod, o.oco_scheduled, o.oco_scheduled_to, o.oco_real_open_date, o.date_first_queued, 

				o.oco_script_sol, o.oco_prior, o.oco_channel, o.oco_tag, o.profile_id, 

				te.*,

				cl.id as client_id, cl.fullname, cl.nickname, 

				i.inst_nome as unidade,

				p.problema as problema, p.prob_area as prob_area_cod, p.prob_sla as sla_solucao_cod,

				a.sistema as area, a.sis_email as area_email, a.sis_atende as sis_atende,

				asol.sistema as area_solicitante, 

				l.local as setor, l.loc_reitoria as reitoria_cod, l.loc_prior as loc_prior_cod, l.loc_dominio as dominio_cod,
				l.loc_predio as predio_cod,

				pr.prior_nivel as prioridade_nivel, pr.prior_sla as sla_resposta_cod,

				u.login as login, u.nome as nome, u.email as user_email, u.AREA as user_area, u.user_admin as user_admin,

				ua.nome as aberto_por, ua.nivel as aberto_por_nivel_cod, ua.email as aberto_por_email, ua.AREA as aberto_por_area, 

				s.status as chamado_status, s.stat_cat as stat_cat_cod, s.stat_painel as stat_painel_cod, s.stat_time_freeze as time_freeze, s.stat_ignored, 

				stc.stc_desc as status_cat,

				sls.slas_desc as sla_solucao, sls.slas_tempo as sla_solucao_tempo,

				slr.slas_desc as sla_resposta, slr.slas_tempo as sla_resposta_tempo,

				sol.script_desc, prioridade_atendimento.pr_nivel as pr_atendimento, prioridade_atendimento.pr_desc as pr_descricao, 
				prioridade_atendimento.pr_color as cor, prioridade_atendimento.pr_font_color as cor_fonte

			FROM
				ocorrencias as o left join clients cl on o.client = cl.id 
				left join sistemas as a on a.sis_id = o.sistema
				left join localizacao as l on l.loc_id = o.local
				left join instituicao as i on i.inst_cod = o.instituicao
				left join usuarios as u on u.user_id = o.operador
				left join usuarios as ua on ua.user_id = o.aberto_por

				left join sistemas as asol on asol.sis_id = ua.AREA

				left join `status` as s on s.stat_id = o.status
				left join status_categ as stc on stc.stc_cod = s.stat_cat
				left join problemas as p on p.prob_id = o.problema
				left join sla_solucao as sls on sls.slas_cod = p.prob_sla
				left join prioridades as pr on pr.prior_cod = l.loc_prior
				left join sla_solucao as slr on slr.slas_cod = pr.prior_sla

				left join script_solution as sol on sol.script_cod = o.oco_script_sol
				
				left join prior_atend as prioridade_atendimento on prioridade_atendimento.pr_cod = o.oco_prior

				left join tickets_extended as te on te.ticket = o.numero

				left join tickets_rated tr on tr.ticket = o.numero
				";


$QRY["ocorrencias_full_ini_count"] = "SELECT count(*) as total 
				FROM
				ocorrencias as o left join sistemas as a on a.sis_id = o.sistema
				left join localizacao as l on l.loc_id = o.local
				left join instituicao as i on i.inst_cod = o.instituicao
				left join usuarios as u on u.user_id = o.operador
				left join usuarios as ua on ua.user_id = o.aberto_por
				left join `status` as s on s.stat_id = o.status
				left join status_categ as stc on stc.stc_cod = s.stat_cat
				left join problemas as p on p.prob_id = o.problema
				left join sla_solucao as sls on sls.slas_cod = p.prob_sla
				left join prioridades as pr on pr.prior_cod = l.loc_prior
				left join sla_solucao as slr on slr.slas_cod = pr.prior_sla

				left join script_solution as sol on sol.script_cod = o.oco_script_sol
				
				left join prior_atend as prioridade_atendimento on prioridade_atendimento.pr_cod = o.oco_prior
				";



// $QRY["useropencall"]= "SELECT c.*, a.*, b.sistema as ownarea, b.sis_id as ownarea_cod ".
// 					"FROM configusercall as c, sistemas as a, sistemas as b ".
// 					"WHERE c.conf_opentoarea = a.sis_id and c.conf_ownarea = b.sis_id and c.conf_cod = 1";//codigo 1 reservado para configuracoes globais

$QRY["useropencall"]= "SELECT c.*, a.* ".
					"FROM configusercall as c, sistemas as a ".
					"WHERE c.conf_opentoarea = a.sis_id and c.conf_cod = 1";//codigo 1 reservado para configuracoes globais


$QRY["useropencall_custom"]= "SELECT c.*, a.* ".
					"FROM configusercall as c, sistemas as a ".
					"WHERE c.conf_opentoarea = a.sis_id ";


$QRY["locais"] = "SELECT l .  * , r.reit_nome, pr.prior_nivel AS prioridade, d.dom_desc AS dominio, 
						pred.pred_desc as predio, 
						sla.slas_desc as tempo_resposta 
		FROM localizacao AS l
		LEFT  JOIN reitorias AS r ON r.reit_cod = l.loc_reitoria
		LEFT  JOIN prioridades AS pr ON pr.prior_cod = l.loc_prior
		LEFT  JOIN dominios AS d ON d.dom_cod = l.loc_dominio
		LEFT JOIN predios as pred on pred.pred_cod = l.loc_predio 
		LEFT JOIN sla_solucao as sla on sla.slas_cod = pr.prior_sla
		";


$QRY["categorias_status"] = "select count(*) total, s.*, stc.* ".
			"from ocorrencias o left join `status` s on o.status = s.stat_id ".
			"left join status_categ stc on stc.stc_cod = s.stat_cat WHERE s.stat_painel in(1,2) group by stc.stc_desc";


?>