# cctv-video-compare
Watch multiple CCTV videos at same time. 

I record my IP Camera CCTV footage using ffmpeg on Ubuntu. Each clip is 5 minutes long stored in a driectory with the same name as the camera. The timestamps drift slightly (by seconds) so I implemented a very crude drift compensation algorithm that someone may help fix.

I have included sample footage from my 3 IP cameras for testing.

Run using php -S localhost:8080
