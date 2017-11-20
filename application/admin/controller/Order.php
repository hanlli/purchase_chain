<?php


namespace app\admin\controller;
use app\common\controller\Admin;
use PHPExcel;
use PHPExcel_IOFactory;

class Order extends Admin {
	protected $warning = null;
	protected $order_error_data = []; //用于指示哪个商品加入订单失败

	//订单列表
	public function index(){
    //判断用户角色决定展示简单版还是完整版的订单列表
    $map = [];
    $filters = [];
    $order = 'date_added desc';
    $order_model = model('Order');
    $list = $order_model->where($map)->order($order)->paginate(30,false,['query'=>$filters]);
    //按照采购方聚合$real_list
    $real_list = [];
    foreach ($list as $lst) {
      // $tmp = $lst->getData();
      $tmp = $lst;
      $id = $tmp['customer_id'];
      if (isset($real_list[$id])) {
         $real_list[$id][$tmp['order_id']] = $tmp;
      } else {
         $real_list[$id] = [$tmp['order_id']=>$tmp];
      }
    }
    //判断每个订单是否存在退货单
    $real_list2 = [];
    $real_list_rowspan = [];
    foreach ($real_list as $key => $rlst) {
      $rowspan = 0;
      $tmp = [];
      foreach ($rlst as $order_id=>$order) {
        $tmp[$order_id] = [];
        $tmp[$order_id][] = $order;
        $refund_orders = $order_model->refund()->where(['order_id'=>$order_id])->select();
        foreach ($refund_orders as $ro) {
          $tmp[$order_id][] = $ro;
          $rowspan++;
        }
        $rowspan++;
        $customer_name = $order['customer_name'];
      }
      $real_list2[$key] = $tmp;
      $real_list_rowspan[$key] = ['rowspan'=>$rowspan+1, 'customer_name'=>$customer_name];
    }
    // 记录当前列表页的cookie
    $data = array(
      // 'list' => $list,
      'list' => $real_list2,
      'page' => $list->render(),
      'real_list_rowspan' => $real_list_rowspan,
    ); 
    $this->assign($data);
		$this->assign('meta_title','订单列表');

		return $this->fetch();
	}
	//订单详情
	public function detail(){
    //判断用户角色决定展示简单版还是完整版的订单列表
    $order_id = input('order_id');
    $order_model = model('Order');
    $opp_model = model('OrderPproduct');
    //普通订单
    $map['order_id'] = $order_id;
    $order = 'order_pproduct_id desc';
    $list = $opp_model->with('order_product')->where($map)->order($order)->paginate(30);
    $data = array(
      'order_info' => $order_model->find($order_id),
      'list' => $list,
      'page' => $list->render(),
    ); 
    //退货单
    $map = [];
    $map['order_id'] = $order_id;
    $refund_model = model('Refund');
    $refunds = $refund_model->where($map)->select();
    if(!empty($refunds)) {
      $rpp_model = model('RefundPproduct');
      foreach ($refunds as $rf) {
        $refund_infos = [];
        $refund_infos['main'] = $rf;
        $rf_products = $rpp_model->with('refund_product')->where(['refund_id'=>$rf['refund_id']])->select();
        $refund_infos['products'] = $rf_products;
        $data['refund_infos'][] = $refund_infos;
      }
    }
    $this->assign($data);
		$this->assign('meta_title','订单详情');
		return $this->fetch();
	}

	/**
   * 添加新建订单接口
   * @author jonathan lee
   */
  public function add() {
    //因为上传
    $products = input('post.');
    $is_valid = $this->validateCartProducts($products);
    if(!$is_valid) {
      $this->error($this->warning, '', $this->order_error_data);
    }
    //调用order model新建订单
    $to_add_items = $products;
    $product_model = model('Product');
    $product_ids = array_keys($to_add_items);
    $items_stock_info = $product_model->getStockInfoByIds($product_ids);
    foreach ($to_add_items as $product_id => $qty) {
      $items_stock_info[$product_id]['quantity'] = $qty;
    }
    $order_model = model('Order');
    $order_id = $order_model->addOrder($items_stock_info);
    //添加订单操作记录
    $oh_model = model('OrderHistory');
    $oh_model->addNewOrderHistory($order_id);

    //删除进货单中已经变成订单的商品
    $cart_model = model('Cart');
    $cart_model->removeItemByIds($product_ids);

    if($order_id) {
      $this->success('成功添加订单', '', []);
    } else {
      $this->error('添加订单失败');
    }
    return;
  }

  /**
   * 为将要加入订单的商品做数量和存在性验证
   * @author jonathan lee
   */
  protected function validateCartProducts($products) {
  	//先检查自有系统中商品库存是否满足条件
  	if(empty($products)) {
      $this->warning = '请输入需要添加的商品';
      return false;
    }
    $to_add_items = $products;
 
    $product_model = model('Product');
    $product_ids = array_keys($to_add_items);
    $items_stock = $product_model->getStockByIds($product_ids);

    foreach ($to_add_items as $product_id => $qty) {
      if(!array_key_exists($product_id, $items_stock)) {
        $items_stock[$product_id] = 0;
      }
      if($qty > ($items_stock[$product_id])) {
        $this->order_error_data[$product_id] = ['product_id'=>$product_id,'max_qty'=>($items_stock[$product_id])];
      }
    }
    if(!empty($this->order_error_data)) {
      $this->warning = '商品库存不足，请检查';
      return false;  
    }
  	//先归类不同商品的不同供应商，暂时只有atelier一家
  	//然后调用每个供应商的库存或者预订单接口检查订单是否能够下成功

  	//如果有不满足情况的商品，则返回商品id和错误信息数组

    return true;
  }

  public function history() {
    $order_id = input('order_id');
    $oh_model = model('OrderHistory');
    $order_histories = $oh_model->where(['order_id'=>$order_id])->order('order_history_id desc')->select();

    $data['order_id'] = $order_id;
    $data['order_histories'] = $order_histories;
    $this->assign($data);
    $this->assign('meta_title','查看修改记录【管理员 销售部】');
    return $this->fetch();
  }
  public function excelDetail(){
      $order_id = 9;
      $order_model = model('Order');
      $opp_model = model('OrderPproduct');
      //普通订单
      $map['order_id'] = $order_id;
      $order = 'order_pproduct_id desc';
      $list = $opp_model->with('order_product')->where($map)->order($order)->select();
      $data['list'] = $list;
      //退货单
      $map = [];
      $map['order_id'] = 9;
      $refund_model = model('Refund');
      $refunds = $refund_model->where($map)->select();
      if(!empty($refunds)) {
          $rpp_model = model('RefundPproduct');
          foreach ($refunds as $rf) {
              $refund_infos = [];
              $refund_infos['main'] = $rf;
              $rf_products = $rpp_model->with('refund_product')->where(['refund_id'=>$rf['refund_id']])->select();
              $refund_infos['products'] = $rf_products;
              $data['refund_infos'][] = $refund_infos;
          }
      }
      //var_dump($data['list'][0]->order_product);die();
      $results = [];
      foreach ($data['list'] as $key=>$val){
         $results[$key]=array(
             $val->code,
             $val->image,
             $val->name,
             $val->category,
             $val->vendor_code,
             $val->msrp,
             $val->discount,
             $val->price,
             $val->color,
         );
         foreach ($val->order_product as $k=>$v){
             $results[$key][9][$k]=[$v->sku,$v->size,$v->quantity];
         }

      }


      $headers = array('商品款号','图片','商品名称','商品分类','供应商代码','市场价','折扣','采购价','颜色','SKU','尺码','数量');
      $this->prepareDownloadExcel($results, $headers, $row_keys=array(), $excel_name = 'Orders', $headers_types=array());


  }
    function prepareDownloadExcel($results, $headers, $row_keys, $excel_name = 'Orders', $headers_types=array()) {

        $objPHPExcel = new PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("Think-tec");
        $objProps->setLastModifiedBy("Think-tec");
        $objProps->setTitle("Think-tec Contact");
        $objProps->setSubject("Think-tec Contact Data");
        $objProps->setDescription("Think-tec Contact Data");
        $objProps->setKeywords("Think-tec Contact");
        $objProps->setCategory("Think-tec");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();

        $objActSheet->setTitle('Sheet1');
        $col_idx = 'A';
        $objActSheet->getRowDimension(1)->setRowHeight(16);
        foreach ($headers as $header) {
            $objActSheet->setCellValue($col_idx++.'1', $header);
        }

        $i = 2;
        foreach ($results as $rlst) {
            $col_idx = 'A';
            array_pop($rlst);
            foreach ($rlst as $v){
                $objActSheet->setCellValue($col_idx++.$i, $v);
            }

            $i++;
        }
        // die();
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        ob_end_clean();
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $excel_name . '_' . date('Y-m-d_H-i',time()) . '.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
        exit;
    }
}