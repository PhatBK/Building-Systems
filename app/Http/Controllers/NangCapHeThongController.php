<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NguyenLieu;
use App\Models\MonAnNguyenLieu;
use App\Models\MonAn;
use App\Models\BuaAn;
use App\Models\MonAn_BuaAn;
use App\Models\User;
use App\Models\TheLoai;
use App\Models\LoaiMon;
use Rap2hpoutre\FastExcel\FastExcel;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\BrowserKit\Client as BaseClient;
use Symfony\Component\BrowserKit\Response;

class NangCapHeThongController extends Controller
{
    protected $BMR_Nam = 0 ;
    protected $BMR_Nu = 0 ;
    protected $BMR_Child = 0 ;
    // tính dinh dưỡng cho một người theo công thức: BMR
    public function tinhBMR($cannang,$chieucao,$tuoi,$gioitinh){
        $calo = 4.186;
        $bmr = 0;
        if(Auth::user()){
            $user = Auth::user();
            $tuoi = $user->tuoi;

            if(strcmp($user->gioitinh,"Nam") == 0){

                $cannang = 0;
                $chieucao = 0;
                $gioitinh = 1;

                if($tuoi < 18){
                    $cannang = rand(30,60);
                    $chieucao = rand(130,165);
                }
                if($tuoi >= 18){
                    $cannang = rand(50,80);
                    $chieucao = rand(160,185);
                }

                $bmr = 10*$cannang + 6.25*$chieucao - 5*$tuoi + 5;
                echo "Ban la : Nam";
                echo "<br>";

            }else if(strcmp($user->gioitinh,"Nữ") == 0){

                $cannang = 0;
                $chieucao = 0;
                $gioitinh = 0;
                if($tuoi < 18){
                    $cannang = rand(30,50);
                    $chieucao = rand(120,150);
                }
                if($tuoi >= 18){
                    $cannang = rand(40,60);
                    $chieucao = rand(140,170);
                }
                $bmr = 10*$cannang + 6.25*$chieucao - 5*$tuoi - 161;
                echo "Ban la :Nu";
                echo "<br>";
            }else if(strcmp($user->gioitinh,"Không Xác Định") == 0){

                $cannang = 0;
                $chieucao = 0;
                if($tuoi < 18){
                    $cannang = (rand(30,50) + rand(30,60))/2;
                    $chieucao = (rand(120,150) + rand(130,165))/2;
                }
                if($tuoi >= 18){
                    $cannang = (rand(40,60) + rand(50,80))/2;
                    $chieucao = (rand(140,170) + rand(160,185))/2;
                }
                $bmrNam = 10*$cannang + 6.25*$chieucao - 5*$tuoi + 5;
                $bmrNu  = 10*$cannang + 6.25*$chieucao - 5*$tuoi - 161;

                $bmr    = rand($bmrNu + 50 ,$bmrNam - 50);
                echo "Ban la : Khong Xac Dinh";
                echo "<br>";
            }
           
            echo "Cam on ban da login";
            echo "<br>";

        }else{

            $cannang    = (float)   $cannang;
            $chieucao   = (float)   $chieucao;
            $tuoi       = (int)     $tuoi;
            $gioitinh   = (int)     $gioitinh;
            if($gioitinh == 0){
                $bmr = 10*$cannang + 6.25*$chieucao - 5*$tuoi - 161;
            }else if($gioitinh == 1){
                $bmr = 10*$cannang + 6.25*$chieucao - 5*$tuoi + 5;
            }else{
                $bmrNam = 10*$cannang + 6.25*$chieucao - 5*$tuoi + 5;
                $bmrNu  = 10*$cannang + 6.25*$chieucao - 5*$tuoi - 161;
                $bmr    = rand($bmrNu + 50 ,$bmrNam - 50);
            }
            
            echo "Ban chua login";
            echo "<br>";
        }
        echo "Nang Luong Ban Can:<br>";
        return $bmr;
    }
    // test API
    public function getMonAn($id){
        $monan = MonAn::find($id);
        $nguyenlieus = $monan->monan_nguyenlieu();
        return response()->json($monan,201);
    }
    public function getChannel(){
        return changeTitle("Nguyễn Huy Phát,sinh viên năm 4 đại học bách khoa");
    	// return view('customer.channel');
    }

    // crawl dữ liệu từ các trang trên thực tế
    public function crawlerAction()
    {

        $url = "https://vi.wikipedia.org/wiki/Vi%E1%BB%87t_Nam";

        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links_count = $crawler->filter('a')->count();
        $title_count = $crawler->filter('title')->count();
        
        $all_titles = array();
        $all_links = array();

        $crawler->filter('body > p')->each(function ($node) {
            echo $node->text()."<br>";
        });

        if($title_count > 0){
            $titles = $crawler->filter('title');
            foreach($titles as $title){
                $all_titles[] = $title;
            }
            $all_titles = array_unique($all_titles);
            // print_r($all_titles);
        }else{
            echo " không tìm thấy title nào ..";
        }
        if($links_count > 0){

            $links = $crawler->filter('a')->links();

            foreach ($links as $link) {
                // $all_links[] = $link->getURI();
                echo $link->getURI()."<br>";
                
                $url_child = $link->getURI();
                $client_child = new Client();

                $crawler_child = $client_child->request('GET', $url_child);
                $links_count_child = $crawler_child->filter('a')->count();

                $all_links_child = array();

                if($links_count_child > 0){
                    $links_child = $crawler_child->filter('a')->links();
                    foreach ($links_child as $link_child) {
                        // $all_links_child[] = $link_child->getURI();
                        echo $link_child->getURI()."<br>";
                    }
                    // $all_links_child = array_unique($all_links_child);
                    // echo "<pre>";
                    // print_r($all_links_child);
                    // echo "</pre>";
                }

            }
            // $all_links = array_unique($all_links);
            // echo "Tất cả các link có sẵn từ trang: $url   <pre>"; print_r($all_links);echo "</pre>";
        } else {
            echo "không có link nào !!";
        }
        die;
    }
    // lấy trang cá nhân một user
    public function getTrangCaNhan($id){
        $user = User::find($id);
        return view('customer.trangcanhan',['user'=>$user]);
    }
    // viết API cho các ứng dụng khác
    public function getAPI(){
        $users = User::all();
        $theloais = TheLoai::all();
        $monans = MonAn::all();
        $buaan = BuaAn::all();
        $monan_buaan = MonAn_BuaAn::all();

        return response()->json([$monans],201);
    }

    // Phần quản lý về nguyên liệu
    public function get_danhsach_nguyenlieu(){
        $nguyenlieus = NguyenLieu::all();
        return view('admin.nguyenlieu.danhsach',['nguyenlieus'=>$nguyenlieus]);
    }
    public function get_them_nguyenlieu(){
        return view('admin.nguyenlieu.them');
    }
    public function post_them_nguyenlieu(Request $req){
        $this->validate($req,
            [
                'anh' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            ],
            [
                'anh.required' => 'Error : chưa chọn ảnh đại diện',
                'anh.image' => 'Error   : ảnh đại diện không đúng đinh dạng ảnh',
                'anh.mimes' => 'Error : chọn file có đuôi: jpeg,png,jpg,gif,svg ',
                'anh.size' => 'Error    : dung lượng ảnh phải <= 4 Mb',
            ]
        );
        // Uploads file
        $file = $req->file('anh');
        $filename = $file->getClientOriginalName();
        $Hinh = str_random(5) . $filename;
        while (file_exists('uploads/nguyenlieu/' . $Hinh)) {
            $Hinh = str_random(5) . $filename;
        }
        $file->move('uploads/nguyenlieu', $Hinh);
        // hết uploads file
        $nguyenlieu = new NguyenLieu;
        $nguyenlieu->ten = $req->ten;
        $nguyenlieu->anh = 'uploads/nguyenlieu/'.$Hinh;

        $nguyenlieu->calos = abs($req->calos);
        $nguyenlieu->carbon = abs($req->carbon);
        $nguyenlieu->xo = abs($req->xo);
        $nguyenlieu->fat = abs($req->fat);
        $nguyenlieu->fat_baohoa = abs($req->fat_baohoa);
        $nguyenlieu->protein = abs($req->protein);

        $nguyenlieu->save();

        return redirect('admin/nguyenlieu/danhsach')->with('thongbao', 'SUCCESS : thêm tài khoản thành công..');

    }
    public function get_sua_nguyenlieu($id){
        $nguyenlieu = NguyenLieu::find($id);
        return view('admin.nguyenlieu.sua',['nguyenlieu'=>$nguyenlieu]);
        // dd($nguyenlieu);
    }
    public function post_sua_nguyenlieu(Request $req){

        dd($req);

    }
    public function get_xoa_nguyenlieu($id){
        $nguyenlieu = NguyenLieu::find($id);
        $nguyenlieu->delete();
        return view('admin.nguyenlieu.danhsach');
    }
    // Phân quản lý về Bữa ăn
    public function get_danhsach_buaan(){

    }
    public function get_them_buaan(){

    }
    public function post_them_buaan(){

    }
    public function get_sua_buaan(){

    }
    public function post_sua_buaan(){

    }
    public function get_xoa_buaan(){

    }
    public function fast_excel(){
        // $users= User::all();
        // (new FastExcel($users))->export('file.xlsx');
        $collection = (new FastExcel)->configureCsv(';',1)->import('foods.csv');
        dd($collection);
    }

}
