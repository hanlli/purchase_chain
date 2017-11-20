<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\model\Atelier as AtApi;

class Atelier extends Command
{
    private $atelier;
    private $vendor_id = 1;

    protected function initialize(Input $input, Output $output)
    {
      $this->atelier = new AtApi(['vendor_id'=>$this->vendor_id]);
    }

    protected function configure()
    {
        $this->setName('atelier')
            ->setDefinition([
                new Option('type', 't', Option::VALUE_OPTIONAL, "Fully init all atelier products.", 'product'),
            ])
            ->setDescription('Sync atelier98 products via api');
    }

    protected function execute(Input $input, Output $output)
    { 
        $type = $input->getOption('type');
        $msg = '';
        switch ($type) {
          case 'product':
            $this->sync($output);
            break;
          case 'full_prepare':
            $this->prepareFullInitData($output);
            break;
          case 'full_init':
            $this->fullInitProducts($output);
            break;
          case 'init_attrs':
            $this->initFourBaseTableByAtelier($output);
            break;
          default:
            $msg = 'Pls input valid type.';
            break;
        }
        $output->writeln($msg);
    }

    protected function getAllGoodDetailLists(Output $output, $pro_list_path, $base_name) {
      $atelier = $this->atelier;
      $res = 'GetGoodsDetailListByPage';
      $start_page = 1;
      $page_size = 2000;
      $page_end = false;
      $MAX_PAGE = 500;
      $products_list = [];
      $output->writeln('Start getting product detail list.');

      if (!file_exists($pro_list_path)) {
          mkdir($pro_list_path, 0777, true);
      }
      //删除老的同步历史文件记录
      $i = $start_page;
      while ($i++ <= $MAX_PAGE) {
        @unlink($pro_list_path . $base_name . $i.".txt");
      }

      while ($start_page <= $MAX_PAGE&&!$page_end) {
        $params['PageNum'] = $start_page;
        $params['PageSize'] = $page_size;
        $api_ret = $atelier->callApi($res, $params);
        if(!isset($api_ret['Good'])) {
          $page_end = true;
          break;
        }
        $output->writeln('Got '.count($api_ret['Good']) . ' products detail.');
        $string_data = serialize($api_ret['Good']);
        file_put_contents($pro_list_path . $base_name . $start_page.".txt", $string_data);
        $start_page++;
      }
    }

    protected function getAllGoodLists(Output $output, $pro_list_path, $base_name) {
      $atelier = $this->atelier;
      $res = 'GetGoodsListByPage';
      $start_page = 1;
      $page_size = 2000;
      $page_end = false;
      $MAX_PAGE = 50;
      $products_list = [];
      $output->writeln('Start getting product list.');

      if (!file_exists($pro_list_path)) {
          mkdir($pro_list_path, 0777, true);
      }
      //删除老的同步历史文件记录
      $i = $start_page;
      while ($i++ <= $MAX_PAGE) {
        @unlink($pro_list_path . $base_name . $i.".txt");
      }
      
      while ($start_page <= $MAX_PAGE&&!$page_end) {
        $params['PageNum'] = $start_page;
        $params['PageSize'] = $page_size;
        $api_ret = $atelier->callApi($res, $params);
        if(!isset($api_ret['Good'])) {
          $page_end = true;
          break;
        }
        $output->writeln('Got '.count($api_ret['Good']) . ' products.');
        $string_data = serialize($api_ret['Good']);
        file_put_contents($pro_list_path . $base_name . $start_page.".txt", $string_data);
        $start_page++;
      }
    }
    /*
    * 拼接本地保存的商品list和商品detail list数据，并将它们按照商品list的大小保存为新的文件们
    */
    protected function combGoodsList(Output $output,$pro_list_path,$good_list_base_name,$good_detail_list_base_name) {
      $MAX_PAGE = 500;
      //将所有list数据保存在atelier_product中
      $atelier_product = db('atelier_product');
      $start_page = 1;
      while ($start_page <= $MAX_PAGE) {
        $file_name = $pro_list_path . $good_list_base_name . $start_page.".txt";
        if(!file_exists($file_name)) {
          break;
        }
        $string_data = file_get_contents($file_name);
        $start_page++;
        $goods = unserialize($string_data);
        foreach ($goods as $gds) {
          $data = ['id'=>$gds['ID'], 'list_data'=>serialize($gds)];
          $atelier_product->insert($data);
        }
      }      
      // 将所有detail_list数据保存在atelier_product中
      $start_page = 1;
      while ($start_page <= $MAX_PAGE) {
        $file_name = $pro_list_path . $good_detail_list_base_name . $start_page.".txt";
        if(!file_exists($file_name)) {
          break;
        }
        $string_data = file_get_contents($file_name);
        $start_page++;
        $goods = unserialize($string_data);
        foreach ($goods as $gds) {
          $map = ['id'=>$gds['ID']];
          $data = ['detail_data'=>serialize($gds)];
          $atelier_product->where($map)->update($data);
        }
      }
    }

    /*
    * 根据atelier_product数据初始化pproduct, pproduct_attr, pproduct_image, product表
    */
    protected function initPproduct($atelier, Output $output) {
      $atelier_product = db('atelier_product');
      $page = 1;
      $listRows = 2000;
      $MAX_PAGE = 1000;

      $output->writeln('Clear all cache.');
      cache(null);
      $output->writeln('Delete all pproducts.');
      db('pproduct')->delete(true);

      $cnt = 0;
      $goods = $atelier_product->page($page++, $listRows)->select();
      while (!empty($goods)&&$page <= $MAX_PAGE) {
        foreach ($goods as $gds) {
          $cnt++;
          $list_data = unserialize($gds['list_data']);
          $detail_data = unserialize($gds['detail_data']);
          $good_data = array_merge($list_data, $detail_data);
          try {
            $atelier->addProduct($good_data);
          } catch (Exception $e) {
          }
          // $output->writeln('Creating '.$good_data['GoodsName']);
          if($cnt%2000 == 0) {
            $output->writeln('Created '.$cnt.' Products.');
          }
        }
        $goods = $atelier_product->page($page++, $listRows)->select();
      }      
      $output->writeln('Totally created ' . $cnt . ' products.');
    }

    protected function prepareFullInitData(Output $output) {
      $pro_list_path = APP_PATH."atelier98_products/";

      //首先获取所有商品list，大约有30M数据，二十分钟左右获取完成
      $output->writeln('Start get all goods list.');
      $good_list_base_name = "full_goods_list_";
      $this->getAllGoodLists($output,$pro_list_path,$good_list_base_name);
      $output->writeln('Finished get all goods list.');

      //然后批量获取所有商品Detail List，大约有100M数据，一个小时左右获取完成
      $output->writeln('Start get all goods detail list.');
      $good_detail_list_base_name = "goods_detail_list_";
      $this->getAllGoodDetailLists($output,$pro_list_path,$good_detail_list_base_name);
      $output->writeln('Finished get all goods detail list.');

      //拼接本地保存的商品list和商品detail list数据，并将它们按照商品list的大小保存为新的文件们
      $output->writeln('Start combine goods list & detail list.');
      $this->combGoodsList($output,$pro_list_path,$good_list_base_name,$good_detail_list_base_name);
      $output->writeln('Finished combine goods list & detail list.');
    }

    protected function fullInitProducts(Output $output) {
      //逐个读取拼接好的文件，并插入数据库
      $output->writeln('Start add goods to db.');
      $atelier = $this->atelier;
      $this->initPproduct($atelier, $output);      
      $output->writeln('Finished add goods to db.');
    }

    /**
     * 初始同步vendor_brand表
     */
    protected function initFourBaseTableByAtelier(Output $output) {
      $output->writeln('Clear all cache...');
      cache(null);
      $output->writeln('initBrand...');
      $this->atelier->initBrand();
      $output->writeln('initBrandByAtelier...');
      $this->atelier->initBrandByAtelier();

      $output->writeln('initGoodsType...');
      $this->atelier->initGoodsType();
      $output->writeln('initGoodsTypeByAtelier...');
      $this->atelier->initGoodsTypeByAtelier();

      $output->writeln('initSeason...');
      $this->atelier->initSeason();
      $output->writeln('initSeasonByAtelier...');
      $this->atelier->initSeasonByAtelier();

      $output->writeln('initCategory...');
      $this->atelier->initCategory();
      $output->writeln('initCategoryByAtelier...');
      $this->atelier->initCategoryByAtelier();

      $output->writeln('Done!');
    }

    public function sync(Output $output) {
      $this->atelier->syncProducts($output);
    }

    public function test(Output $output) {
      $this->atelier->syncProducts($output);
    }
}