# cctv-video-compare
Watch multiple CCTV videos at the same time. 

I record my IP Camera CCTV footage using ffmpeg on Ubuntu. Each clip is 5 minutes long stored in a directory with the same name as the camera. The timestamps drift slightly (by seconds) so I implemented a very crude drift compensation algorithm that someone may help fix. I really needed to write this code as I don't use any NVR and watching 3 clips in 3 open instances of VLC at the same time is not easy to do.

![Interface](https://github.com/wilwad/cctv-video-compare/blob/master/video-compare.png)

I have included sample footage from my 3 IP cameras for testing.

Run using php -S localhost:8080
