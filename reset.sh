#!/bin/bash
git restore upload/jp_graph/graphs/error_log
git pull origin master --rebase
sudo chown shipsenergy:shipsenergy /home/shipsenergy/public_html/upload/*
sudo chown shipsenergy:shipsenergy /home/shipsenergy/public_html/upload/jp_graph/graphs/*
sudo chown shipsenergy:shipsenergy /home/shipsenergy/public_html/erms/includes/*
sudo chown shipsenergy:shipsenergy /home/shipsenergy/public_html/upload/jp_graph/graphs/src/*
sudo chown shipsenergy:shipsenergy /home/shipsenergy/public_html/erms/includes/src/*
sudo systemctl restart apache_exporter.service
sudo systemctl restart httpd
echo "Ready"