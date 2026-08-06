@extends("template.landing.appLanding")

@section("content")
    <!-- banner area start -->
    <div class="banner-area-start">
        <div class="container-full-header">
            <div class="row">
                <div class="col-lg-12">
                    <div class="banner-area-one rts-section-gap bg_image">
                        <div class="banner-content-area">
                            <div class="pre-title wow fadeInUp" data-wow-delay=".0s" data-wow-duration=".8s">
                                <img src="{{ asset("landing/assets/images/banner/icon/08.svg") }}" alt="icons">
                                <span>Your Health Our Priority</span>
                            </div>
                            <h1 class="title wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">Your
                                Health <br>
                                Our Priority</h1>
                            <p class="disc wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                                We provide comprehensive healthcare services with a personal touch, ensuring you
                                receive
                                the best care possible.
                            </p>
                            <div class="select-area-down wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                                <form action="#" method="get" accept-charset="utf-8">
                                    <select name="my_select" class="mySelect">
                                        <option value="2" selected>Select Department</option>
                                        <option value="10">Medicle</option>
                                        <option value="1">Surgery</option>
                                        <option value="13">Neurosugery</option>
                                    </select>
                                </form>
                                <form class="select-2" action="#" method="get" accept-charset="utf-8">
                                    <select name="my_select2" class="my_select2">
                                        <option value="2" selected>Select Doctor</option>
                                        <option value="10">Mr.Mark</option>
                                        <option value="1">Mrs. Sajjat</option>
                                        <option value="13">John Dion</option>
                                    </select>
                                </form>
                                <a href="#" class="rts-btn btn-primary">Find Doctors</a>
                            </div>
                        </div>
                        <!-- person image -->
                        <div class="person-image">
                            <img src="{{ asset("landing/assets/images/banner/01.png") }}" alt="heart">
                            <div class="single-tag wow zoomIn" data-wow-delay=".2s" data-wow-duration=".8s">
                                <img src="{{ asset("landing/assets/images/banner/icons/heart.svg") }}" alt="heart">
                                <span>Cardiology</span>
                            </div>
                            <div class="single-tag two wow zoomIn" data-wow-delay=".4s" data-wow-duration=".8s">
                                <img src="{{ asset("landing/assets/images/banner/icons/neuron.svg") }}" alt="heart">
                                <span>Neurology</span>
                            </div>
                            <div class="single-tag three wow zoomIn" data-wow-delay=".6s" data-wow-duration=".8s">
                                <img src="{{ asset("landing/assets/images/banner/icons/orthopedics.svg") }}" alt="heart">
                                <span>Orthopedics</span>
                            </div>
                        </div>
                        <!-- person image -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- banner area end -->

    <!-- short service -->
    <div class="short-service-area rts-section-gap2">
        <div class="container">
            <div class="row g-5">
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 wow fadeInRight" data-wow-delay=".2s"
                    data-wow-duration=".8s">
                    <a href="/login" class="single-short-service">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/01.svg") }}" alt="service">
                        </div>
                        <h5 class="title">
                            Request <br> Appointment
                        </h5>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 wow fadeInRight" data-wow-delay=".4s"
                    data-wow-duration=".8s">
                    <a href="{{ route("jadwalDokter.index") }}" class="single-short-service">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/02.svg") }}" alt="service">
                        </div>
                        <h5 class="title">
                            Jadwal <br>
                            Dokter
                        </h5>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 wow fadeInRight" data-wow-delay=".6s"
                    data-wow-duration=".8s">
                    <a href="tel:+4733378901" class="single-short-service">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/03.svg") }}" alt="service">
                        </div>
                        <h5 class="title">
                            Emergency <br>
                            Call
                        </h5>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 wow fadeInRight" data-wow-delay=".8s"
                    data-wow-duration=".8s">
                    <a href="contactus.html" class="single-short-service">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/04.svg") }}" alt="service">
                        </div>
                        <h5 class="title">
                            24/7 <br>
                            Support
                        </h5>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- short service end -->

    <!-- rs about area start -->
    <div class="rts-about-area rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="about-image-left-wrapper">
                        <div class="thumbnail-main move-right wow">
                            <img src="{{ asset("landing/assets/images/about/01.jpg") }}" alt="about">
                        </div>
                        <div class="review-area">
                            <img src="{{ asset("landing/assets/images/about/02.jpg") }}" alt="">
                            <div class="stars-area">
                                <div class="wrapper">
                                    <div class="single">
                                        <i class="fa-sharp fa-solid fa-star"></i>
                                    </div>
                                    <div class="single">
                                        <i class="fa-sharp fa-solid fa-star"></i>
                                    </div>
                                    <div class="single">
                                        <i class="fa-sharp fa-solid fa-star"></i>
                                    </div>
                                    <div class="single">
                                        <i class="fa-sharp fa-solid fa-star"></i>
                                    </div>
                                    <div class="single">
                                        <i class="fa-sharp fa-solid fa-star"></i>
                                    </div>
                                </div>
                                <div class="content">
                                    <span>TrustScore 4.8</span> |
                                    <span>2k reviews</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1 mt--30">
                    <div class="about-content-wrapper-right">
                        <div class="title-wrapper-left">
                            <span class="pre wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">About
                                Us</span>
                            <h2 class="title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                                Providing Exceptional <br> Healthcare with a focus <br> on patient.
                            </h2>
                        </div>
                        <p class="disc wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                            At Mediweb, our mission is to provide exceptional healthcare services with a focus on
                            patient-centered care. We are dedicated to improving the health and well-being of our
                            community through.
                        </p>
                        <div class="button-wrapper wow fadeInUp" data-wow-delay=".8s" data-wow-duration=".8s">
                            <a href="about.html" class="rts-btn btn-primary">About Us <img
                                    src="{{ asset("landing/assets/images/banner/icons/arrow--up-right.svg") }}"
                                    alt=""></a>
                            <div class="signature">
                                <img class="one" src="{{ asset("landing/assets/images/about/01.png") }}"
                                    alt="about">
                                <img class="sign" src="{{ asset("landing/assets/images/about/02.png") }}"
                                    alt="about">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rs about area end -->

    <!-- srvice area start -->
    <div class="service-area position-relative rts-section-gapBottom">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 col-md-6">
                    <div class="title-wrapper-left">
                        <span class="pre wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">Our
                            Services</span>
                        <h2 class="title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                            we provide a wide <br> range of medical <br> services
                        </h2>
                        <p class="disc wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                            Our experienced and dedicated teams are committed to providing comprehensive
                            patient-centered care to meet your unique health needs.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/09.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Cardiology</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/10.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Neurology</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/11.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Dental Care</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/12.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Mental Health</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/13.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Medicine</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".8s" data-wow-duration=".8s">
                    <div class="single-service-area">
                        <div class="icon">
                            <img src="{{ asset("landing/assets/images/service/14.svg") }}" alt="service">
                        </div>
                        <h4 class="title">Orthopedics</h4>
                        <p class="disc">
                            Diagnosis and treatment of heart & cardiovascular conditions.
                        </p>
                        <a href="service-details.html" class="btn-transparent">Learn More <i
                                class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="service-content-iamge">
            <img src="{{ asset("landing/assets/images/service/13.png") }}" alt="service">
        </div>
    </div>
    <!-- srvice area end -->

    <!-- why choose us section start -->
    <div class="why-choose-us-area">
        <div class="container-80">
            <div class="row">
                <div class="col-lg-12">
                    <div class="why-choose-area-wrapper bg-light  rts-section-gap ">
                        <div class="container">
                            <div class="row align-items-end">
                                <div class="col-xl-5 col-lg-6 mb_md--80 mb_sm--60">
                                    <div class="why-choose-us-area-wrapper-main">
                                        <div class="title-wrapper-left">
                                            <span class="pre wow fadeInUp" data-wow-delay=".2s"
                                                data-wow-duration=".8s">Why Choose Us</span>
                                            <h2 class="title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                                                Why Our Patients Recommend Us
                                            </h2>
                                            <p class="disc wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                                                we are dedicated to providing exceptional medical care.
                                            </p>
                                        </div>
                                        <div class="why-choose-us-main-wrapper">
                                            <div class="single-choose-us wow fadeInLeft" data-wow-delay=".2s"
                                                data-wow-duration=".8s">
                                                <div class="icon">
                                                    <img src="{{ asset("landing/assets/images/service/icon/01.svg") }}"
                                                        alt="service">
                                                </div>
                                                <div class="info">
                                                    <h6 class="title">Patient-Centered Care</h6>
                                                    <p>Emphasize your commitment to patient well-being</p>
                                                </div>
                                            </div>
                                            <div class="single-choose-us wow fadeInLeft" data-wow-delay=".4s"
                                                data-wow-duration=".8s">
                                                <div class="icon">
                                                    <img src="{{ asset("landing/assets/images/service/icon/02.svg") }}"
                                                        alt="service">
                                                </div>
                                                <div class="info">
                                                    <h6 class="title">Emergency Support</h6>
                                                    <p>Emphasize your commitment to patient well-being</p>
                                                </div>
                                            </div>
                                            <div class="single-choose-us wow fadeInLeft" data-wow-delay=".6s"
                                                data-wow-duration=".8s">
                                                <div class="icon">
                                                    <img src="{{ asset("landing/assets/images/service/icon/03.svg") }}"
                                                        alt="service">
                                                </div>
                                                <div class="info">
                                                    <h6 class="title">Expertise and Experience</h6>
                                                    <p>Emphasize your commitment to patient well-being</p>
                                                </div>
                                            </div>
                                            <div class="single-choose-us wow fadeInLeft" data-wow-delay=".8s"
                                                data-wow-duration=".8s">
                                                <div class="icon">
                                                    <img src="{{ asset("landing/assets/images/service/icon/04.svg") }}"
                                                        alt="service">
                                                </div>
                                                <div class="info">
                                                    <h6 class="title">24/7 hour Emergency Call</h6>
                                                    <p>Emphasize your commitment to patient well-being</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 offset-xl-1">
                                    <div class="right-whychoose-us-style-one">
                                        <div class="feature-bg-primary">
                                            <img src="{{ asset("landing/assets/images/feature/02.webp") }}"
                                                alt="medicle">
                                        </div>
                                        <div class="thumbnail-image">
                                            <img src="{{ asset("landing/assets/images/feature/01.webp") }}"
                                                alt="medicle">
                                        </div>
                                        <div class="inner-content">
                                            <div class="top">
                                                <h3 class="title">Hello!</h3>
                                                <div class="time-shedule">
                                                    <img src="{{ asset("landing/assets/images/feature/03.svg") }}"
                                                        alt="medicle">
                                                    <span>24/7</span>
                                                </div>
                                            </div>
                                            <div class="call-us">
                                                <img src="{{ asset("landing/assets/images/feature/04.svg") }}"
                                                    alt="medicle">
                                                <span>1-800-100-900</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- why choose us section end -->

    <!-- book your consulting -->
    <div class="book-your-consulting rts-section-gap">
        <div class="container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="appoinment-area-main bg_image">
                        <h2 class="title">Book your Free Consulting</h2>
                        <form action="#">
                            <div class="half-input-wrapper">
                                <input type="text" placeholder="Patient name">
                                <input type="text" placeholder="Phone Number">
                            </div>
                            <div class="search-input">
                                <select class="">
                                    <option data-display="Select Department">Doctor Department</option>
                                    <option value="1">Medicine</option>
                                    <option value="2">Cardiology</option>
                                    <option value="3">Operation</option>
                                </select>
                                <div class="nice-select custom-select one" style="flex-basis: 50%;" tabindex="0">
                                    <span class="current">Select Department</span>
                                    <ul class="list">
                                        <li data-value="Morning 10 AM" data-display="Morning 10 AM"
                                            class="option selected focus">Medicine</li>
                                        <li data-value="1" class="option">Sergary</li>
                                        <li data-value="2" class="option">Casualty</li>
                                        <li data-value="3" class="option">Cardiology </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="search-input">
                                <select class="">
                                    <option data-display="Select Time">Doctor Department</option>
                                    <option value="1">Medicine</option>
                                    <option value="2">Cardiology</option>
                                    <option value="3">Operation</option>
                                </select>
                                <div class="nice-select custom-select one" style="flex-basis: 50%;" tabindex="0">
                                    <span class="current">Select Doctor</span>
                                    <ul class="list">
                                        <li data-value="Morning 10 AM" data-display="Morning 10 AM"
                                            class="option selected focus">Mr. Kadir</li>
                                        <li data-value="1" class="option">David John Lee</li>
                                        <li data-value="2" class="option">Mark John</li>
                                        <li data-value="3" class="option">Kapoor Sharma</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="input-half-wrapper datepicker-wrapper">
                                <input type="text" id="datepicker" placeholder="mm/dd/yyyy">
                                <div class="search-input">
                                    <select class="">
                                        <option data-display="Select Time">Doctor Department</option>
                                        <option value="1">Medicine</option>
                                        <option value="2">Cardiology</option>
                                        <option value="3">Operation</option>
                                    </select>
                                    <div class="nice-select custom-select one" style="flex-basis: 50%;" tabindex="0">
                                        <span class="current">Select Doctor Time</span>
                                        <ul class="list">
                                            <li data-value="Morning 10 AM" data-display="Morning 10 AM"
                                                class="option selected focus">Morning 10 AM</li>
                                            <li data-value="1" class="option">Morning 11 AM</li>
                                            <li data-value="2" class="option">Morning 12 AM</li>
                                            <li data-value="3" class="option">Evening 5 PM</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <a href="#" class="rts-btn btn-primary">Appoinment <img
                                    src="{{ asset("landing/assets/images/banner/icons/arrow--up-right.svg") }}"
                                    alt=""></a>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="thumbnail-appoinment wow move-right">
                        <img src="{{ asset("landing/assets/images/appoinment/01.webp") }}" alt="appoinment">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- book your consulting end -->

    <!-- pricing area start -->
    <div class="pricing-area-start rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-area-center">
                        <span class="pre">Pricing Plan</span>
                        <h2 class="title text-center">Our Pricing Best Plane</h2>
                    </div>
                </div>
            </div>
            <div class="row g-75 mt--0">
                <div class="col-lg-4 col-md-6 col-sm-12 wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">
                    <div class="single-pricing-area">
                        <div class="pricing-head">
                            <span>Basic Plan</span>
                            <h2 class="price">$29</h2>
                            <p>per/month</p>
                        </div>
                        <div class="body">
                            <p class="disc">
                                Ages16 - 24, Unlimited Play, Junior
                                Golf Program
                            </p>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Echocardiogram</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Stress Testing</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Cardiac Catheterization</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Angioplasty and Stenting</span>
                            </div>
                            <a href="#" class="rts-btn btn-primary">Booking Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                    <div class="single-pricing-area active">
                        <div class="pricing-head">
                            <span>Silver Plan</span>
                            <h2 class="price">$66</h2>
                            <p>per/month</p>
                        </div>
                        <div class="body">
                            <p class="disc">
                                30 Rounds per Month, Pro Shop
                                Discount
                            </p>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Echocardiogram</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Stress Testing</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Cardiac Catheterization</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Angioplasty and Stenting</span>
                            </div>
                            <a href="#" class="rts-btn btn-primary">Booking Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12 wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                    <div class="single-pricing-area">
                        <div class="pricing-head">
                            <span>Gold Plan</span>
                            <h2 class="price">$99</h2>
                            <p>per/month</p>
                        </div>
                        <div class="body">
                            <p class="disc">
                                Ages16 - 24, Unlimited Play, Junior
                                Golf Program
                            </p>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Echocardiogram</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Stress Testing</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Cardiac Catheterization</span>
                            </div>
                            <div class="single-check">
                                <i class="fa-light fa-check"></i>
                                <span>Angioplasty and Stenting</span>
                            </div>
                            <a href="#" class="rts-btn btn-primary">Booking Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- pricing area end -->

    <!-- why team us section start -->
    <div class="team-area-start">
        <div class="container-80">
            <div class="row">
                <div class="col-lg-12">
                    <div class="team-style-wrapper bg-light  rts-section-gap ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="title-between-area">
                                        <div class="title-wrapper-left">
                                            <span class="pre wow fadeInUp" data-wow-delay=".2s"
                                                data-wow-duration=".8s">Our Doctors</span>
                                            <h2 class="title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                                                Discover Expert <br> Team Member
                                            </h2>
                                        </div>
                                        <p class="disc wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">
                                            our team of dedicated and highly skilled doctors is at the heart of our
                                            commitment to providing exceptional medical care. Each member of our
                                            team
                                            brings a wealth of knowledge, experience.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-5 mt--20">
                                <div class="col-lg-12">
                                    <div class="team-swiper-wrapper-1">
                                        <div class="swiper team-swiper-container-h1">
                                            <div class="swiper-wrapper">
                                                @php
                                                    $displayDoctors = $landingDoctors !== []
                                                        ? $landingDoctors
                                                        : [
                                                            [
                                                                "doctor_name" => "Dr. Rachel Evans",
                                                                "photo_url" => asset("landing/assets/images/team/01.jpg"),
                                                                "doctor_label" => "MBBS, MD - Oncologists",
                                                            ],
                                                            [
                                                                "doctor_name" => "Dr. Emily Carter",
                                                                "photo_url" => asset("landing/assets/images/team/02.jpg"),
                                                                "doctor_label" => "MBBS, MD - Oncologists",
                                                            ],
                                                            [
                                                                "doctor_name" => "Dr. Lisa Morgan",
                                                                "photo_url" => asset("landing/assets/images/team/03.jpg"),
                                                                "doctor_label" => "MBBS, MD - Oncologists",
                                                            ],
                                                            [
                                                                "doctor_name" => "Dr. Jessica Lee",
                                                                "photo_url" => asset("landing/assets/images/team/04.jpg"),
                                                                "doctor_label" => "MBBS, MD - Oncologists",
                                                            ],
                                                        ];
                                                @endphp
                                                @foreach ($displayDoctors as $doctor)
                                                    <div class="swiper-slide">
                                                        <div class="single-team-area-start">
                                                            <a href="{{ route("jadwalDokter.index") }}" class="thumbnail">
                                                                <img src="{{ $doctor["photo_url"] }}"
                                                                    alt="Foto {{ $doctor["doctor_name"] }}"
                                                                    width="450" height="450" loading="lazy"
                                                                    decoding="async">
                                                            </a>
                                                            <div class="bottom">
                                                                <a href="{{ route("jadwalDokter.index") }}">
                                                                    <h6 class="title">{{ $doctor["doctor_name"] }}</h6>
                                                                </a>
                                                                <p>{{ $doctor["doctor_label"] ?? "Dokter RS Arsy" }}</p>
                                                                <div class="social-area-tranaparent">
                                                                    <ul>
                                                                        <li>
                                                                            <a href="{{ route("jadwalDokter.index") }}"
                                                                                aria-label="Lihat jadwal {{ $doctor["doctor_name"] }}">
                                                                                <i class="fa-solid fa-calendar-days"></i>
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="swiper-pagination"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- why team us section end -->

    <!-- rts testimonials area start -->
    <div class="rts-testimonials-area rts-section-gap">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-area-center">
                        <span class="pre">Client Feedback</span>
                        <h2 class="title text-center">Words from Our <br> Patients</h2>
                    </div>
                </div>
            </div>
            <div class="row mt--0 g-5">
                <div class="col-lg-12">
                    <div class="swiper swiper-container-h1">
                        <div class="swiper-wrapper">

                            <div class="swiper-slide">
                                <div class="single-testimonials-style">
                                    <div class="quots">
                                        <img src="{{ asset("landing/assets/images/testimonials/quotes.png") }}"
                                            alt="testimonails">
                                    </div>
                                    <p class="disc">
                                        Dr. Robert Thompson is an exceptional cardiologist. His ability to explain
                                        with
                                        complex medical issues in a way that's easy to understand is truly
                                        impressive.
                                    </p>
                                    <div class="author-area">
                                        <a href="#" class="img">
                                            <img src="{{ asset("landing/assets/images/testimonials/01.png") }}"
                                                alt="testiminials">
                                        </a>
                                        <div class="info">
                                            <h6 class="name">David Patel</h6>
                                            <div class="stars-area">
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shape">
                                        <img src="{{ asset("landing/assets/images/testimonials/02.png") }}"
                                            alt="service">
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="single-testimonials-style">
                                    <div class="quots">
                                        <img src="{{ asset("landing/assets/images/testimonials/quotes.png") }}"
                                            alt="testimonails">
                                    </div>
                                    <p class="disc">
                                        An exceptional cardiologist. His ability to explain with
                                        complex medical issues in a way that's easy to understand is truly
                                        impressive
                                        Thomas.
                                    </p>
                                    <div class="author-area">
                                        <a href="#" class="img">
                                            <img src="{{ asset("landing/assets/images/testimonials/03.png") }}"
                                                alt="testiminials">
                                        </a>
                                        <div class="info">
                                            <h6 class="name">Malan Patel</h6>
                                            <div class="stars-area">
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shape">
                                        <img src="{{ asset("landing/assets/images/testimonials/02.png") }}"
                                            alt="service">
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="single-testimonials-style">
                                    <div class="quots">
                                        <img src="{{ asset("landing/assets/images/testimonials/quotes.png") }}"
                                            alt="testimonails">
                                    </div>
                                    <p class="disc">
                                        Dr. Robert Thompson is an exceptional cardiologist. His ability to explain
                                        with
                                        complex medical issues in a way that's easy to understand is truly
                                        impressive.
                                    </p>
                                    <div class="author-area">
                                        <a href="#" class="img">
                                            <img src="{{ asset("landing/assets/images/testimonials/04.png") }}"
                                                alt="testiminials">
                                        </a>
                                        <div class="info">
                                            <h6 class="name">Joker Vandi</h6>
                                            <div class="stars-area">
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shape">
                                        <img src="{{ asset("landing/assets/images/testimonials/02.png") }}"
                                            alt="service">
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="single-testimonials-style">
                                    <div class="quots">
                                        <img src="{{ asset("landing/assets/images/testimonials/quotes.png") }}"
                                            alt="testimonails">
                                    </div>
                                    <p class="disc">
                                        Dr. Robert Thompson is an exceptional cardiologist. His ability to explain
                                        with
                                        complex medical issues in a way that's easy to understand is truly
                                        impressive.
                                    </p>
                                    <div class="author-area">
                                        <a href="#" class="img">
                                            <img src="{{ asset("landing/assets/images/testimonials/01.png") }}"
                                                alt="testiminials">
                                        </a>
                                        <div class="info">
                                            <h6 class="name">David Patel</h6>
                                            <div class="stars-area">
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                                <i class="fa-sharp fa-solid fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shape">
                                        <img src="{{ asset("landing/assets/images/testimonials/02.png") }}"
                                            alt="service">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rts testimonials area end -->

    <!-- request appoinment area start -->
    <div class="request-appoinment-area rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="request-appoinemnt-area-main-wrapper bg_image rts-section-gap">
                        <span class="pre">Book Appointment</span>
                        <h2 class="title">
                            Request Your <br>
                            <span>Appointment</span>
                        </h2>
                        <a href="/login" class="rts-btn btn-primary">Get Appointment</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- request appoinment area end -->
@endsection
