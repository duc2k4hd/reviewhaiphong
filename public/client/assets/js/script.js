$(document).ready(function () {

    localStorage.clear();

    $("body").addClass('loading');

    $('.bars-mobile').on('click', function(e) {

        $('.header-menu').fadeIn(500);

        $(this).hide();

        $('.advertisement').fadeIn(500);

    });

    $('.close-mobile').on('click', function(e) {

        $('.header-menu').fadeOut(300);

        $('.advertisement').fadeOut(100);

        setTimeout(function() {

            $('.bars-mobile').show();

        }, 300);

    });



    $('#search-form').on('submit', function (e) {

        e.preventDefault();

        let keyword = $.trim($('#keyword').val());

    

        if (keyword.length === 0) return;

    

        if (keyword.length > 150) {

            alert('Vui lòng nhập dưới 150 ký tự!');

            return;

        }

    

        // Kiểm tra các ký tự hoặc chuỗi nguy hiểm (XSS)

        let xssPattern = /<[^>]*>|<script.*?>|javascript:|onerror=|onload=/gi;

        if (xssPattern.test(keyword)) {

            alert('Dữ liệu bạn nhập vào có chứa mã độc hoặc không hợp lệ!');

            return;

        }

    

        window.location.href = '/tim-kiem/' + encodeURIComponent(keyword);

    });

    

    // Hàm fetch API chung

    async function fetchApi(url, method = 'GET', headers = {}, body = null) {

        try {

            const response = await fetch(url, {

                method: method,

                headers: {

                    'Content-Type': 'application/json',

                    ...headers

                },

                body: body ? JSON.stringify(body) : null

            });



            if (!response.ok) {

                throw new Error('Network response was not ok');

            }



            return await response.json();

        } catch (error) {

            console.error('Fetch error:', error);

            throw error;

        }

    }



    // Hàm random mảng

    function shuffleArray(arr) {

        for (let i = arr.length - 1; i > 0; i--) {

            const j = Math.floor(Math.random() * (i + 1));

            [arr[i], arr[j]] = [arr[j], arr[i]];  // Hoán đổi các phần tử

        }

    }

    

    // Chuyển định dạng datetime sang dd/mm/yy

    function formatDate(dateString) {

        try {

            const date = new Date(dateString);

            return date.toLocaleDateString('vi-VN'); // luôn trả về ngày hợp lệ dạng 30/03/2025

        } catch {

            return '';

        }

    }

    



    // Load categories

    function handleCategory() {

        const categoryApiUri = '/api/category';

        

        // Kiểm tra xem dữ liệu đã có trong localStorage chưa

        const cachedCategory = localStorage.getItem('categories');

        if (cachedCategory) {

            const data = JSON.parse(cachedCategory);

            renderCategory(data); 

        } else {

            fetchApi(categoryApiUri)

                .then(data => {

                    if (data.status === 'success') {

                        localStorage.setItem('categories', JSON.stringify(data));  // Lưu vào localStorage

                        renderCategory(data); 

                    }

                })

                .catch(error => console.error(error));

        }

    }



    function renderCategory(data) {

        const categoryDiv = $('.header-menu .menu');

        const categoryLi = data.data.map(category =>

            `<li><a href="/${category.slug}">${category.name}</a></li>`

        ).join('');

        categoryDiv.html(`<ul>${categoryLi}</ul>`);

    }



    function handlePostsNew() {

        const url = '/api/posts-new';



        const cachedPostsNew = localStorage.getItem('posts-new');

        if (cachedPostsNew) {

            const data = JSON.parse(cachedPostsNew);

            renderPostsNew(data);  

        } else {

            fetchApi(url)

                .then(data => {

                    if (data.status === 'success') {

                        localStorage.setItem('posts-new', JSON.stringify(data));

                        renderPostsNew(data); 

                    }

                })

                .catch(error => console.error(error));

        }

    }



    function renderPostsNew(data) {

        shuffleArray(data.data);

        const newsItems = data.data.map(post =>

            `<div class="news-item">

                <a href="/${post.slug}">

                    <div class="image">

                        <img width="100%" height="100%" loading='lazy' src="/client/assets/images/posts/${post.seo_image}" alt="${post.seo_title}" title="${post.seo_title}">

                    </div>

                    <div class="content">

                        <h2>${post.seo_title}</h2>

                        <p class="description">${post.seo_desc}</p>

                        <p><span style="color: rgb(31, 190, 234); text-decoration: underline;">${post.account.profile.name}</span> - <span>${formatDate(post.published_at)}</span></p>

                    </div>

                    <div class="category-by">

                        <span>${post.category.name}</span>

                    </div>

                </a>

            </div>`

        ).join('');

        $('.latest-news .news-list').append(newsItems);

    }



    function handlePostCategory() {

        const url = '/api/posts-category';

        const postCategory = $('.discover-news .discover .review');



        const cachedPostCategory = localStorage.getItem('posts-category');

        if (cachedPostCategory) {

            const data = JSON.parse(cachedPostCategory);

            renderPostCategory(data); 

        } else {

            fetchApi(url)

                .then(data => {

                    if (data.status === 'success') {

                        localStorage.setItem('posts-category', JSON.stringify(data));  // Lưu vào localStorage

                        renderPostCategory(data);  

                    }

                })

                .catch(error => console.error(error));

        }

    }



    function renderPostCategory(data) {

        shuffleArray(data.data);

        data.data.map(post => {

            $('.discover-news .discover .review').append(

                `<div class="experience">

                    <div class="title">

                        <h3>${post.name}</h3>

                    </div>

                    <div class="content">

                        ${$.map(post.posts, item => {

                            return `<div class="item">

                                        <a href="/${item.slug}">

                                            <div class="image">

                                                <img width="100%" height="100%" loading='lazy' src="/client/assets/images/posts/${item.seo_image}" alt="${item.seo_title}" title="${item.seo_title}">

                                            </div>

                                            <div class="content">

                                                <div class="title">

                                                    <h4>${item.seo_title}</h4>

                                                </div>

                                                <div class="post-date">

                                                    <p>${item.account.profile.name} - ${formatDate(item.published_at)} <span class="star"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span></p>

                                                </div>

                                                <div class="description">

                                                    <p>${item.seo_desc}</p>

                                                </div>

                                            </div>

                                        </a>

                                    </div>`;

                        }).join('')}

                    </div>

                    <div class="view-more"><a href="">Xem thêm</a></div>

                </div>`

            );

        });

    }



    function handlePostFeature() {

        const url = '/api/feutured-posts';



        const cachedPostFeature = localStorage.getItem('featured-posts');

        if (cachedPostFeature) {

            const data = JSON.parse(cachedPostFeature);

            renderPostFeature(data);  

        } else {

            fetchApi(url)

                .then(data => {

                    if (data.status === 'success') {

                        localStorage.setItem('featured-posts', JSON.stringify(data));  // Lưu vào localStorage

                        renderPostFeature(data);  

                    }

                })

                .catch(error => console.error(error));

        }

    }



    function renderPostFeature(data) {

        shuffleArray(data.data);

        data.data.map(post => {

            $('.featured-posts-container .featured-posts-list').append(

                `<a href="/${post.slug}" class="post-item">

                    <div class="post-image">

                        <img loading='lazy' src="/client/assets/images/posts/${post.seo_image}" title="${post.seo_title}" alt="${post.seo_title}">

                        <p class="post-category">${post.category.name}</p>

                    </div>

                    <div class="post-content">

                        <h5 class="post-title">${post.seo_title}</h5>

                        <div class="post-meta">

                            <span class="post-date"><i class="far fa-calendar-alt"></i> ${formatDate(post.published_at)}</span>

                            <span class="post-author"><i class="far fa-user"></i> ${post.account.profile.name}</span>

                        </div>

                    </div>

                </a>`

            );

        });

    }



    // Redirect to home

    $('.review-haiphong .header .header-logo .logo').on('click', function() {

        window.location.href = '/';

    })



    // Scrolling

    $(window).scroll(function() {

        if ($(this).scrollTop() > 300) {

            $('.scrolling').fadeIn();

        } else {

            $('.scrolling').fadeOut();

        }

    });



    $('.scrolling').click(function() {

        $('html, body').animate({

            scrollTop: 0

        }, 600);

        return false;

    });



    // Định dạng thứ ngày tháng năm

    function formatWeek() {

        const weekday = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];



        const today = new Date();

        const dayName = weekday[today.getDay()]; // lấy tên thứ



        const day = String(today.getDate()).padStart(2, '0');

        const month = String(today.getMonth() + 1).padStart(2, '0'); // tháng bắt đầu từ 0

        const year = today.getFullYear();



        const formattedDate = `${dayName}, ${day}/${month}/${year}`;



        $('.date-location .date').text(formattedDate);

    }



    // Chát AI

    function openAIChat() {

        alert("🚀 Xin chào! Tôi là AI.");

    }

    $('.openAIChat').on('click', function(event) {

        event.preventDefault();

        alert("🚀 Xin chào! Tôi là AI.");

    });



    

    async function getLocation() {

        var lat = '';

        var lon = '';

        await fetchApi('https://ipinfo.io/?token=5b1ce4ac2e60f0')

            .then(data => {

                lat = data.loc.split(',')[0];

                lon = data.loc.split(',')[1];

                $('.location').text(data.city);

        })

        const doC = await getTemperature(lat, lon);



        $('.temperature').text(doC + " °C");

    }

    

    function error(err) {

        console.warn(`Lỗi khi lấy vị trí: ${err.message}`);

    }

    

    // function getProvinceFromCoordinates(lat, lon) {

    //     const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=vi`;

    

    //     return fetchApi(url).then(data => {

    //         if (data && data.address) {

    //             const address = data.address;

    //             const province = address.city || address.state || address.town || address.region;

    //             return province;

    //         } else {

    //             console.log("Không tìm thấy tỉnh/thành phố từ vị trí.");

    //             return null;

    //         }

    //     }).catch(error => {

    //         console.error("Lỗi khi gọi API:", error);

    //         return null;

    //     });

    // }



    function getTemperature(lat, lon) {

        const apiKey = '0544e7a77737475aaea101759250304';

        const url = `https://api.weatherapi.com/v1/current.json?key=${apiKey}&q=${lat},${lon}`;

    

        return fetchApi(url)

            .then(data => {

                if (data && data.current) {

                    const temperature = data.current.temp_c;

                    return temperature;

                } else {

                    console.log("Không lấy được dữ liệu thời tiết.");

                    return null;

                }

            })

            .catch(error => {

                console.error("Lỗi khi gọi WeatherAPI:", error);

                return null;

            });

    }



    handleCategory();

    handlePostsNew();

    



    setTimeout(function() {

        handlePostCategory();

        handlePostFeature();

        formatWeek();

        getLocation();

        // formatGeolocation();

        $("body").removeClass('loading');

        $('.loader').hide();

    }, 500);

});

