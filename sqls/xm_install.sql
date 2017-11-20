/*日期  2017-01-11 Jon 品牌表*/
DROP TABLE IF EXISTS `brand`;
CREATE TABLE IF NOT EXISTS `brand` (
  `brand_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='品牌表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 供应商品牌映射表*/
DROP TABLE IF EXISTS `vendor_brand`;
CREATE TABLE IF NOT EXISTS `vendor_brand` (
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `brand_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台brand_id',
  `vendor_brand_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处brand_id',
  `name` varchar(255) DEFAULT '' COMMENT '供应商处品牌名称'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商品牌映射表';
/*日期  2017-01-11 Jon 热销品牌表*/
DROP TABLE IF EXISTS `hot_brand`;
CREATE TABLE IF NOT EXISTS `hot_brand` (
  `hot_brand_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL COMMENT '平台brand_id',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`hot_brand_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='热销品牌表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 颜色表*/
-- DROP TABLE IF EXISTS `color`;
-- CREATE TABLE IF NOT EXISTS `color` (
--   `color_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `name` varchar(255) DEFAULT '' COMMENT '英文名',
--   `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
--   `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
--   `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
--   `date_added` datetime NOT NULL,
--   PRIMARY KEY (`color_id`)
-- ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='颜色表' AUTO_INCREMENT=1 ;
-- /*日期  2017-01-11 Jon 供应商颜色映射表*/
-- DROP TABLE IF EXISTS `vendor_color`;
-- CREATE TABLE IF NOT EXISTS `vendor_color` (
--   `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
--   `color_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台color_id',
--   `vendor_color_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处color_id',
--   `name` varchar(255) DEFAULT '' COMMENT '供应商处颜色名称'
-- ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商颜色映射表';

/*日期  2017-01-11 Jon 商品性别表*/
DROP TABLE IF EXISTS `goods_type`;
CREATE TABLE IF NOT EXISTS `goods_type` (
  `goods_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`goods_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品性别表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 供应商商品性别映射表*/
DROP TABLE IF EXISTS `vendor_goods_type`;
CREATE TABLE IF NOT EXISTS `vendor_goods_type` (
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `goods_type_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台goods_type_id',
  `vendor_goods_type_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处goods_type_id',
  `name` varchar(255) DEFAULT '' COMMENT '供应商处商品性别名称'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品性别映射表';

/*日期  2017-01-11 Jon 商品季节表*/
DROP TABLE IF EXISTS `season`;
CREATE TABLE IF NOT EXISTS `season` (
  `season_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(128) DEFAULT '' COMMENT '中文名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`season_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品季节表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 供应商商品季节映射表*/
DROP TABLE IF EXISTS `vendor_season`;
CREATE TABLE IF NOT EXISTS `vendor_season` (
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `season_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台season_id',
  `vendor_season_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处season_id',
  `name` varchar(128) DEFAULT '' COMMENT '供应商处商品季节名称'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品季节映射表';

/*日期  2017-01-11 Jon 商品品类表*/
DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类父ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品品类表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 供应商商品品类映射表*/
DROP TABLE IF EXISTS `vendor_category`;
CREATE TABLE IF NOT EXISTS `vendor_category` (
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台category_id',
  `vendor_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处category_id',
  `name` varchar(255) DEFAULT '' COMMENT '供应商处商品品类名称'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品品类映射表';
/*日期  2017-01-11 Jon 商品母品类表*/
DROP TABLE IF EXISTS `parent_category`;
CREATE TABLE IF NOT EXISTS `parent_category` (
  `parent_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`parent_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品母品类表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 供应商商品母品类映射表*/
DROP TABLE IF EXISTS `vendor_parent_category`;
CREATE TABLE IF NOT EXISTS `vendor_parent_category` (
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `parent_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台parent_category_id',
  `vendor_parent_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处parent_category_id',
  `name` varchar(255) DEFAULT '' COMMENT '供应商处商品母品类名称'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品母品类映射表';

/*日期  2017-01-11 Jon 币种表*/
DROP TABLE IF EXISTS `currency`;
CREATE TABLE IF NOT EXISTS `currency` (
  `currency_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol_left` varchar(12) NOT NULL,
  `symbol_right` varchar(12) NOT NULL,
  `decimal_place` char(1) NOT NULL,
  `value` float(15,8) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='币种表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 母商品表*/
DROP TABLE IF EXISTS `pproduct`;
CREATE TABLE IF NOT EXISTS `pproduct` (
  `pproduct_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `country_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属国家ID',
  `vendor_product_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商处product_id',
  `brand_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台brand_id',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台category_id',
  `parent_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台parent_category_id',
  `goods_type_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台goods_type_id',
  `season_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台season_id',
  `vendor_code` varchar(255) DEFAULT '' COMMENT '供应商代码',
  `code` varchar(512) NOT NULL DEFAULT '' COMMENT '商品全款号',
  `spu` varchar(512) DEFAULT '' COMMENT '',
  `name` varchar(255) DEFAULT '' COMMENT '英文名',
  `chs_name` varchar(255) DEFAULT '' COMMENT '中文名',
  `image` varchar(255) DEFAULT '' COMMENT '主图片thumb',
  `currency_id` int(11) NOT NULL DEFAULT '0' COMMENT '货币ID',
  `discount` decimal(15,4) NOT NULL DEFAULT '10.0000' COMMENT '折扣',
  `price` decimal(15,4) NOT NULL DEFAULT '10000.0000',
  `cost` decimal(15,4) NOT NULL DEFAULT '10000.0000',
  `msrp` decimal(15,4) NOT NULL DEFAULT '10000.0000',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`pproduct_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='母商品表' AUTO_INCREMENT=1 ;
ALTER TABLE `pproduct` ADD UNIQUE INDEX `idx_vendor_vendor_product` (`vendor_id`,`vendor_product_id`); 

/*日期  2017-01-11 Jon 母商品属性表*/
DROP TABLE IF EXISTS `pproduct_attr`;
CREATE TABLE IF NOT EXISTS `pproduct_attr` (
  `pproduct_attr_id` int(11) NOT NULL AUTO_INCREMENT,
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `super_color` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(255) NOT NULL DEFAULT '',
  `fabric` varchar(512) NOT NULL DEFAULT '',
  `composition` varchar(512) NOT NULL DEFAULT '',
  `size_and_fit` text NOT NULL,
  `made_in` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`pproduct_attr_id`),
  CONSTRAINT `pproduct_attr_id_fk_1` 
    FOREIGN KEY (`pproduct_id`) 
    REFERENCES `pproduct` (`pproduct_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='母商品属性表' AUTO_INCREMENT=1 ;
ALTER TABLE `pproduct_attr` ADD CONSTRAINT `uc_pproduct_id` UNIQUE (`pproduct_id`);

/*日期  2017-01-11 Jon 子商品表*/
DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `sku` varchar(512) DEFAULT '' COMMENT '',
  `size` varchar(64) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  PRIMARY KEY (`product_id`),
  CONSTRAINT `product_id_fk_1` 
    FOREIGN KEY (`pproduct_id`) 
    REFERENCES `pproduct` (`pproduct_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='子商品表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 推荐商品表*/
DROP TABLE IF EXISTS `recommended_pproduct`;
CREATE TABLE IF NOT EXISTS `recommended_pproduct` (
  `recommended_pproduct_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pproduct_id` int(11) NOT NULL COMMENT '平台brand_id',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`recommended_pproduct_id`),
  CONSTRAINT `recommended_product_id_fk_1` 
    FOREIGN KEY (`pproduct_id`) 
    REFERENCES `pproduct` (`pproduct_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='推荐商品表' AUTO_INCREMENT=1 ;
ALTER TABLE `recommended_pproduct` ADD CONSTRAINT `uc_recommended_pproduct_id` UNIQUE (`pproduct_id`);

/*日期  2017-01-11 Jon 商品图片表*/
DROP TABLE IF EXISTS `pproduct_image`;
CREATE TABLE IF NOT EXISTS `pproduct_image` (
  `pproduct_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `thumb_image` varchar(255) DEFAULT '' COMMENT '',
  `image` varchar(255) DEFAULT '' COMMENT '',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`pproduct_image_id`),
  CONSTRAINT `pproduct_image_id_fk_1` 
    FOREIGN KEY (`pproduct_id`) 
    REFERENCES `pproduct` (`pproduct_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品图片表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 供应商表*/
DROP TABLE IF EXISTS `vendor`;
CREATE TABLE IF NOT EXISTS `vendor` (
  `vendor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '供应商名称',
  `prefix` varchar(20) DEFAULT '' COMMENT '供应商前缀：IS、HM etc',
  `country_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属国家ID',
  `currency_id` int(11) NOT NULL DEFAULT '0' COMMENT '货币ID',
  `discount_mode` int(11) NOT NULL DEFAULT '1' COMMENT '折扣模式：1:季节折扣，2:品牌折扣',
  `vendor_code` varchar(255) DEFAULT '' COMMENT '供应商代码',
  `channel` varchar(255) NOT NULL COMMENT '开拓渠道',
  `sort_order` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 季节折扣规则表*/
DROP TABLE IF EXISTS `season_discount_rule`;
CREATE TABLE IF NOT EXISTS `season_discount_rule` (
  `season_discount_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `season_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台season_id',
  `discount` decimal(15,4) NOT NULL DEFAULT '10.0000' COMMENT '折扣',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未应用,1:已应用,2:应用中',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`season_discount_rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='季节折扣规则表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 当前使用的季节折扣规则表*/
DROP TABLE IF EXISTS `cur_season_discount_rule`;
CREATE TABLE IF NOT EXISTS `cur_season_discount_rule` (
  `cur_season_discount_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `season_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台season_id',
  `discount` decimal(15,4) NOT NULL DEFAULT '10.0000' COMMENT '折扣',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  PRIMARY KEY (`cur_season_discount_rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='当前使用的季节折扣规则表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 品牌折扣规则表*/
DROP TABLE IF EXISTS `brand_discount_rule`;
CREATE TABLE IF NOT EXISTS `brand_discount_rule` (
  `brand_discount_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `brand_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台brand_id',
  `discount` decimal(15,4) NOT NULL DEFAULT '10.0000' COMMENT '折扣',
  `mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加减价模式：1:市场价减价模式，2:零售价减价模式，3:成本价加价模式',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未应用,1:已应用,2:应用中',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`brand_discount_rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='品牌折扣规则表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 当前使用品牌折扣规则表*/
DROP TABLE IF EXISTS `cur_brand_discount_rule`;
CREATE TABLE IF NOT EXISTS `cur_brand_discount_rule` (
  `cur_brand_discount_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台vendor_id',
  `brand_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台brand_id',
  `discount` decimal(15,4) NOT NULL DEFAULT '10.0000' COMMENT '折扣',
  `mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加减价模式：1:市场价减价模式，2:零售价减价模式，3:成本价加价模式',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  PRIMARY KEY (`cur_brand_discount_rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='当前使用品牌折扣规则表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 销售系数表，全平台通用*/
DROP TABLE IF EXISTS `sales_factor`;
CREATE TABLE IF NOT EXISTS `sales_factor` (
  `sales_factor_id` int(11) NOT NULL AUTO_INCREMENT,
  `factor` decimal(15,4) NOT NULL DEFAULT '1.0000' COMMENT '系数本体',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`sales_factor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='销售系数表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 当前使用销售系数表，全平台通用*/
DROP TABLE IF EXISTS `cur_sales_factor`;
CREATE TABLE IF NOT EXISTS `cur_sales_factor` (
  `cur_sales_factor_id` int(11) NOT NULL AUTO_INCREMENT,
  `factor` decimal(15,4) NOT NULL DEFAULT '1.0000' COMMENT '系数本体',
  `from_time` datetime NOT NULL COMMENT '规则生效时间',
  PRIMARY KEY (`cur_sales_factor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='当前使用销售系数表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 进货单*/
DROP TABLE IF EXISTS `cart_product`;
DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '会员ID',
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `country_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属国家ID',
  PRIMARY KEY (`cart_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='进货单' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 进货商品单*/
CREATE TABLE IF NOT EXISTS `cart_product` (
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL COMMENT '子商品ID',
  `quantity` int(11) NOT NULL DEFAULT '0',
  `checked` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否被勾选',
  `date_added` datetime NOT NULL,
  KEY (`cart_id`),
  CONSTRAINT `cart_id_fk_1` 
    FOREIGN KEY (`cart_id`) 
    REFERENCES `cart` (`cart_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='进货商品单' AUTO_INCREMENT=1 ;
ALTER TABLE `cart_product` ADD UNIQUE INDEX `idx_cart_id_product_id` (`cart_id`,`product_id`); 

/*日期  2017-01-11 Jon 订单表*/
DROP TABLE IF EXISTS `order`;
CREATE TABLE IF NOT EXISTS `order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_order_id` varchar(255) NOT NULL DEFAULT '' COMMENT '供应商订单ID',
  `vendor_order_status_name` varchar(255) NOT NULL DEFAULT '' COMMENT '供应商订单状态',
  `vendor_order_status_code` varchar(255) NOT NULL DEFAULT '' COMMENT '供应商订单状态code',
  `vendor_detail` text NOT NULL COMMENT '供应商处订单详情',
  `vendor_order_placed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '供应商处订单是否已经下过',
  `customer_id` int(11) NOT NULL COMMENT '会员ID',
  `customer_group_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员组ID',
  `customer_name` varchar(32) NOT NULL DEFAULT '' COMMENT '会员姓名',
  `customer_telephone` varchar(16) NOT NULL DEFAULT '' COMMENT '会员手机',
  `comment` text NOT NULL COMMENT '订单备注',
  `currency_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Total所用货币ID',
  `currency_code` varchar(3) NOT NULL,
  `currency_value` float(15,8) NOT NULL,
  `total_num` int(11) NOT NULL DEFAULT '0' COMMENT '商品总数',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `order_status_id` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态',
  `payer_account` varchar(512) NOT NULL DEFAULT '' COMMENT '付款账号',
  `reciever_account` varchar(512) NOT NULL DEFAULT '' COMMENT '收款账号',
  `recieved_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '收款金额',
  `ip` varchar(40) NOT NULL DEFAULT '' COMMENT '下单人IP地址',
  `user_agent` varchar(255) NOT NULL DEFAULT '' COMMENT '下单人浏览器',
  `expected_time` datetime NOT NULL COMMENT '期望收货时间',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `shipping_name` varchar(32) NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `shipping_company` varchar(32) NOT NULL DEFAULT '' COMMENT '收货人公司',
  `shipping_telephone` varchar(16) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `shipping_postcode` varchar(10) NOT NULL DEFAULT '' COMMENT '收货地址邮件',
  `shipping_zone` varchar(128) NOT NULL DEFAULT '' COMMENT '收货地址省市',
  `shipping_zone_id` int(11) NOT NULL DEFAULT '0' COMMENT '收货地址省市',
  `shipping_city` varchar(128) NOT NULL DEFAULT '' COMMENT '收货地址市县',
  `shipping_city_id` int(11) NOT NULL DEFAULT '0' COMMENT '收货地址市县',
  `shipping_area` varchar(128) NOT NULL DEFAULT '' COMMENT '收货地址县区',
  `shipping_area_id` int(11) NOT NULL DEFAULT '0' COMMENT '收货地址县区',
  `shipping_method` varchar(128) NOT NULL DEFAULT '' COMMENT '配送方式',
  `shipping_code` varchar(128) NOT NULL DEFAULT '' COMMENT '配送方式code',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 订单商品表*/
DROP TABLE IF EXISTS `order_pproduct`;
CREATE TABLE IF NOT EXISTS `order_pproduct` (
  `order_pproduct_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单号',
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `spu` varchar(512) DEFAULT '' COMMENT '',
  `code` varchar(512) NOT NULL DEFAULT '' COMMENT '商品全款号',
  `image` varchar(255) DEFAULT '' COMMENT '主图片thumb',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台category_id',
  `vendor_code` varchar(255) DEFAULT '' COMMENT '供应商代码',
  `color` varchar(255) NOT NULL DEFAULT '',
  `cost` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '成本单价',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '采购单价',
  `msrp` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '市场价',
  `discount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '折扣',
  PRIMARY KEY (`order_pproduct_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 订单商品表*/
DROP TABLE IF EXISTS `order_product`;
CREATE TABLE IF NOT EXISTS `order_product` (
  `order_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_pproduct_id` int(11) NOT NULL COMMENT '母订单商品表号',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `sku` varchar(512) DEFAULT '' COMMENT '',
  `size` varchar(64) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  PRIMARY KEY (`order_product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 退货表*/
DROP TABLE IF EXISTS `refund`;
CREATE TABLE IF NOT EXISTS `refund` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单号',
  `comment` text NOT NULL COMMENT '订单备注',
  `total_num` int(11) NOT NULL DEFAULT '0' COMMENT '商品总数',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `refund_status_id` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态',
  `payer_account` varchar(512) NOT NULL DEFAULT '' COMMENT '付款账号',
  `reciever_account` varchar(512) NOT NULL DEFAULT '' COMMENT '收款账号',
  `recieved_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '收款金额',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`refund_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='退货表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 订单商品表*/
DROP TABLE IF EXISTS `refund_pproduct`;
CREATE TABLE IF NOT EXISTS `refund_pproduct` (
  `refund_pproduct_id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_id` int(11) NOT NULL COMMENT '订单号',
  `order_pproduct_id` int(11) NOT NULL COMMENT '母订单商品表号',
  `pproduct_id` int(11) NOT NULL COMMENT '母商品ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `spu` varchar(512) DEFAULT '' COMMENT '',
  `code` varchar(512) NOT NULL DEFAULT '' COMMENT '商品全款号',
  `image` varchar(255) DEFAULT '' COMMENT '主图片thumb',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '平台category_id',
  `vendor_code` varchar(255) DEFAULT '' COMMENT '供应商代码',
  `color` varchar(255) NOT NULL DEFAULT '',
  `cost` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '成本单价',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '采购单价',
  `msrp` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '市场价',
  `discount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '折扣',
  PRIMARY KEY (`refund_pproduct_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 订单商品表*/
DROP TABLE IF EXISTS `refund_product`;
CREATE TABLE IF NOT EXISTS `refund_product` (
  `refund_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_pproduct_id` int(11) NOT NULL COMMENT '母订单商品表号',
  `order_product_id` int(11) NOT NULL COMMENT '订单子商品表号',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `sku` varchar(512) DEFAULT '' COMMENT '',
  `size` varchar(64) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  PRIMARY KEY (`refund_product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 订单状态表*/
DROP TABLE IF EXISTS `order_history`;
CREATE TABLE IF NOT EXISTS `order_history` (
  `order_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT '0',
  `before_order_status_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改前状态',
  `order_status_id` int(11) NOT NULL DEFAULT '0',
  `refund_id` int(11) NOT NULL DEFAULT '0',
  `before_refund_status_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改前状态',
  `refund_status_id` int(11) NOT NULL DEFAULT '0',
  `operator_id` int(11) NOT NULL DEFAULT '0',
  `operator_name` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_history_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单状态表' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 用户表*/
DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_group_id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(40) NOT NULL,
  `salt` varchar(9) NOT NULL,
  `fullname` varchar(32) NOT NULL,
  `email` varchar(96) NOT NULL,
  `image` varchar(255) NOT NULL,
  `code` varchar(40) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 用户扩展属性表*/
DROP TABLE IF EXISTS `user_extend`;
CREATE TABLE IF NOT EXISTS `user_extend` (
  `user_extend_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(128) NOT NULL COMMENT '公司名称',
  `company_address` varchar(255) NOT NULL COMMENT '公司地址',
  `contact_name` varchar(64) NOT NULL COMMENT '对接人',
  `contact_phone` varchar(32) NOT NULL COMMENT '对接人联系方式',
  `channel` varchar(255) NOT NULL COMMENT '开拓渠道',
  `sales_name` varchar(255) NOT NULL COMMENT '业务员',
  PRIMARY KEY (`user_extend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
/*日期  2017-01-11 Jon 用户组表*/
DROP TABLE IF EXISTS `user_group`;
CREATE TABLE IF NOT EXISTS `user_group` (
  `user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `permission` text NOT NULL,
  PRIMARY KEY (`user_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 国家省市县表*/
DROP TABLE IF EXISTS `country`;
CREATE TABLE IF NOT EXISTS `country` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `iso_code_2` varchar(2) NOT NULL,
  `iso_code_3` varchar(3) NOT NULL,
  `address_format` text NOT NULL,
  `postcode_required` tinyint(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`country_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
DROP TABLE IF EXISTS `zone`;
CREATE TABLE IF NOT EXISTS `zone` (
  `zone_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
DROP TABLE IF EXISTS `city`;
CREATE TABLE `city` (
  `city_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `area`;
CREATE TABLE `area` (
  `area_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`area_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*日期  2017-01-25 Jon Atelier商品同步中间表*/
DROP TABLE IF EXISTS `atelier_product`;
CREATE TABLE IF NOT EXISTS `atelier_product` (
  `id` int(11) COMMENT 'Atelier商品ID',
  `list_data` text COMMENT '列表接口返回数据',
  `detail_data` text COMMENT '详情接口返回数据'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Atelier商品同步中间表' AUTO_INCREMENT=1 ;
ALTER TABLE `atelier_product` ADD UNIQUE INDEX `idx_id` (`id`); 

/*日期  2017-02-13 Jon 增加消息队列数据库*/
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*日期  2017-02-14 Jon 供应商商品同步最新数据中间表*/
DROP TABLE IF EXISTS `vendor_sync`;
CREATE TABLE IF NOT EXISTS `vendor_sync` (
  `vendor_sync_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商ID',
  `last_synced_time` datetime NOT NULL COMMENT '时区为供应商接口时区',
  PRIMARY KEY (`vendor_sync_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品同步最新数据中间表' AUTO_INCREMENT=1 ;
ALTER TABLE `vendor_sync` ADD CONSTRAINT `uc_vendor_id` UNIQUE (`vendor_id`);

/*日期  2017-01-11 Jon 供应商商品同步记录表*/
DROP TABLE IF EXISTS `vendor_sync_history`;
CREATE TABLE IF NOT EXISTS `vendor_sync_history` (
  `vendor_sync_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商ID',
  `updated_number` int(11) NOT NULL DEFAULT '0' COMMENT '更新商品数量',
  `created_number` int(11) NOT NULL DEFAULT '0' COMMENT '新增商品数量',
  `start_time` datetime NOT NULL COMMENT '时区为本地时区',
  `end_time` datetime NOT NULL COMMENT '时区为本地时区',
  PRIMARY KEY (`vendor_sync_history_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品同步记录表' AUTO_INCREMENT=1 ;

/*日期  2017-02-14 Jon 供应商商品同步错误信息记录*/
DROP TABLE IF EXISTS `sync_error_log`;
CREATE TABLE IF NOT EXISTS `sync_error_log` (
  `sync_error_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商ID',
  `type` varchar(255) NOT NULL DEFAULT 'product' COMMENT '同步接口类型product,attr,discount,etc.',
  `message` text COMMENT '错误信息',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`sync_error_log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商商品同步错误信息记录' AUTO_INCREMENT=1 ;

/*日期  2017-01-11 Jon 记录同步时新增的季节、分类的属性*/
DROP TABLE IF EXISTS `missing_vendor_attr`;
CREATE TABLE IF NOT EXISTS `missing_vendor_attr` (
  `missing_vendor_attr_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商ID',
  `type` varchar(255) DEFAULT '' COMMENT 'brand, goods_type, season, category, parent_category',
  `name` varchar(255) DEFAULT '' COMMENT '供应商处名称',
  `key_id` varchar(255) DEFAULT '' COMMENT '此属性在供应商处ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未更新, 1:已通过接口更新字段',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`missing_vendor_attr_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='记录同步时新增的季节、分类的属性' AUTO_INCREMENT=1 ;
ALTER TABLE `missing_vendor_attr` ADD UNIQUE INDEX `idx_vendor_type_key_id` (`vendor_id`,`type`,`key_id`); 

/*日期  2017-01-11 Jon 记录同步时新增的季节、分类的属性*/
DROP TABLE IF EXISTS `missing_attr_pproduct`;
CREATE TABLE IF NOT EXISTS `missing_attr_pproduct` (
  `missing_attr_pproduct_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商ID',
  `type` varchar(255) DEFAULT '' COMMENT 'brand, goods_type, season, category, parent_category',
  `key_id` varchar(255) DEFAULT '' COMMENT '此属性在供应商处ID',
  `pproduct_id` int(11) NOT NULL DEFAULT '0' COMMENT '母商品ID',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`missing_attr_pproduct_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='记录同步时新增的季节、分类的属性' AUTO_INCREMENT=1 ;
ALTER TABLE `missing_attr_pproduct` ADD UNIQUE INDEX `idx_vendor_type_key_id_pproduct_id` (`vendor_id`,`type`,`key_id`,`pproduct_id`); 

/*日期  2017-03-01 Jon 销售系数表增加状态*/
ALTER TABLE `sales_factor` ADD `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未应用,1:已应用,2:应用中';

/*日期  2017-03-1 xiaoqiao 客户信息字段拓展*/
ALTER TABLE `member` ADD `company_name` varchar(128) NOT NULL COMMENT '公司名称';
ALTER TABLE `member` ADD  `company_address` varchar(255) NOT NULL COMMENT '公司地址';
ALTER TABLE `member` ADD  `contact_name` varchar(64) NOT NULL COMMENT '对接人';
ALTER TABLE `member` ADD  `contact_phone` varchar(32) NOT NULL COMMENT '对接人联系方式';
ALTER TABLE `member` ADD  `channel` varchar(255) NOT NULL COMMENT '开拓渠道';
ALTER TABLE `member` ADD  `sales_name` varchar(255) NOT NULL COMMENT '业务员';
