<?php

use Alexeyplodenko\Sitecode\Http\Middlewares\PageCacheMiddleware;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Services\BladeView;
use Alexeyplodenko\Sitecode\Services\PagesRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\View\Factory as ViewFactory;

//Route::get('/', [HomeController::class, 'index']);
//Route::get('/about-us', [AboutUsController::class, 'index']);
//Route::get('/catalogue/{category}', [CategoryController::class, 'index']);
//Route::get('/catalogue/{category}/{product}', [ProductController::class, 'index']);

$appHost = appHost();

Route::get('{any}', function () {
//    $page = Page::query()->select(['content', 'view'])->where('url', '/')->first();
//    /** @var \Illuminate\View\View $viewFactory */
//    $viewFactory = view('home', ['page' => $page->setNoContentAction(Page::ON_NO_CONTENT_OUTPUT_PLACEHOLDER)]);
//d($viewFactory->getEngine()->get());
//    return $viewFactory;

//    $viewPath = resource_path('views/home.blade.php');
//    $bladeContent = file_get_contents($viewPath);

//    $bladeView = BladeView::fromFile($viewPath);
//    $bladeView->getPhpFragments();

//    $phpCode = Blade::compileString($bladeContent);
//    $phpFragments = extractPhpFragments($phpCode);
//d($phpFragments, $phpCode);
//
////    view();
//    $factory = app(ViewFactory::class);
//    /** @var \Illuminate\View\View $view */
//    $view = $factory->make('home', ['page' => $page], []);
//    $viewRender = $view->render();
//d($viewRender);

    $url = '/'. trim(Request::path(), '/');

    /** @var PagesRepository $pages */
    $pages = app(PagesRepository::class);
    $page = $pages->findByUrl($url);
    if (!$page) {
        abort(404);
    }

    $page->loadSharedContent();

    $view = viewFromPath($page->view);
    return view($view, ['page' => $page]);
})->where('any', '.*')->domain($appHost)->middleware(PageCacheMiddleware::class);
