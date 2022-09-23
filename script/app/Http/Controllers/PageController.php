<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Cviebrock\EloquentSluggable\Services\SlugService;
use voku\helper\AntiXSS;
use App\Models\Settings;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.pages.index')->with('pages', Page::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.pages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'title' => 'required|max:255|min:2',
            'slug' => 'required|unique:pages|alpha_dash',
            'content' => 'required|min:2',
            'status' => 'boolean|required',
        ]);
        
        $antiXss = new AntiXSS();

        $antiXss->removeEvilAttributes(array('style'));

        $antiXss->removeEvilHtmlTags(array('iframe'));

        $description = $antiXss->xss_clean($request->content);

        $page = new Page();
        $page->title = $request->title;
        $page->status = $request->status;
        $page->content = $description;
        $page->slug = SlugService::createSlug(Page::class, 'slug', $request->title);
        $page->save();

        session()->flash('success', 'Page Created Successfuly');
        return redirect(route('pages.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show($page)
    {

        

        $page = Page::where("status", "=", 1)->where("slug", "=", $page)->first();

        $title = translate('Default Title', 'seo');
        $description = translate('Default Description', 'seo');
        $keyword = translate('Default keywords', 'seo');
        $canonical = url()->current();
        SEOMeta::setTitle($title . ' ' .Settings::selectSettings('separator'). ' ' . $page->title);
        SEOMeta::setDescription($description);
        SEOMeta::setKeywords($keyword);
        SEOMeta::setCanonical($canonical);
        OpenGraph::setTitle($title . ' ' .Settings::selectSettings('separator'). ' ' . $page->title);
        OpenGraph::setDescription($description);
        OpenGraph::setSiteName(Settings::selectSettings('name'));
        OpenGraph::addImage(asset(Settings::selectSettings('og_image')));
        OpenGraph::setUrl($canonical);
        OpenGraph::addProperty('type', 'article');

        return view('frontend.page', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        return view('backend.pages.edit')->with('page', $page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {

        //dd($request);
        $request->validate([
            'title' => 'required|max:255|min:2',
            'slug' => 'required|alpha_dash|unique:pages,slug,' . $page->id,
            'content' => 'required|min:2',
            'status' => 'boolean|required',
        ]);

        $antiXss = new AntiXSS();

        $antiXss->removeEvilAttributes(array('style'));

        $antiXss->removeEvilHtmlTags(array('iframe'));

        $description = $antiXss->xss_clean($request->content);

        $page->update([
            $page->title = $request->title,
            $page->status = $request->status,
            $page->content = $description,
            $page->slug = $request->slug
        ]);


        session()->flash('success', 'Page Updated Successfuly');
        return redirect(route('pages.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        $page->delete();

        session()->flash('success', 'Page Deleted Successfuly');

        return redirect(route('pages.index'));
    }


    public function checkSlug(Request $request)
    {
        $slug = SlugService::createSlug(Page::class, 'slug', $request->title);

        return response()->json(['slug' => $slug]);
    }


    public function upload(Request $request)
{
    if($request->hasFile('upload')) {
        //get filename with extension
        $filenamewithextension = $request->file('upload')->getClientOriginalName();
   
        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
   
        //get file extension
        $extension = $request->file('upload')->getClientOriginalExtension();
   
        //filename to store
        $filenametostore = $filename.'_'.time().'.'.$extension;
   
        //Upload File
        $request->file('upload')->move('./uploads/', $filenametostore);

      // $file->move('./uploads/', $filenametostore);
 
        $CKEditorFuncNum = $request->input('CKEditorFuncNum');
        $url = asset("/uploads/".$filenametostore); 
        $msg = 'Image successfully uploaded'; 
        $re = "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$msg')</script>";
          
        // Render HTML output 
        @header('Content-type: text/html; charset=utf-8'); 
        echo $re;
    }
}


}
