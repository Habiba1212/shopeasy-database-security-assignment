# Latest Amazon Linux 2023 AMI for EC2 app servers
data "aws_ami" "amazon_linux_2023" {
  most_recent = true
  owners      = ["amazon"]

  filter {
    name   = "name"
    values = ["al2023-ami-2023.*-x86_64"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}

# Self-signed certificate for lab/demo HTTPS
resource "tls_private_key" "shopeasy_tls_key" {
  algorithm = "RSA"
  rsa_bits  = 2048
}

resource "tls_self_signed_cert" "shopeasy_tls_cert" {
  private_key_pem = tls_private_key.shopeasy_tls_key.private_key_pem

  subject {
    common_name  = "shopeasy.local"
    organization = "ShopEasy"
  }

  validity_period_hours = 8760

  allowed_uses = [
    "key_encipherment",
    "digital_signature",
    "server_auth"
  ]
}

# Import lab/demo certificate into ACM
resource "aws_acm_certificate" "shopeasy_cert" {
  private_key      = tls_private_key.shopeasy_tls_key.private_key_pem
  certificate_body = tls_self_signed_cert.shopeasy_tls_cert.cert_pem

  tags = {
    Name = "${var.project_name}-acm-certificate"
  }
}

# Application Load Balancer in public subnets
resource "aws_lb" "shopeasy_alb" {
  name               = "${var.project_name}-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]

  subnets = [
    aws_subnet.public_az1.id,
    aws_subnet.public_az2.id
  ]

  tags = {
    Name = "${var.project_name}-alb"
  }
}

# Target group for EC2 app servers
resource "aws_lb_target_group" "shopeasy_tg" {
  name        = "${var.project_name}-tg"
  port        = 80
  protocol    = "HTTP"
  vpc_id      = aws_vpc.shopeasy_vpc.id
  target_type = "instance"

  health_check {
    path                = "/"
    protocol            = "HTTP"
    matcher             = "200"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 3
  }

  tags = {
    Name = "${var.project_name}-target-group"
  }
}

# HTTP listener for testing
resource "aws_lb_listener" "http_listener" {
  load_balancer_arn = aws_lb.shopeasy_alb.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.shopeasy_tg.arn
  }
}

# HTTPS listener for SSL/TLS configuration
resource "aws_lb_listener" "https_listener" {
  load_balancer_arn = aws_lb.shopeasy_alb.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"
  certificate_arn   = aws_acm_certificate.shopeasy_cert.arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.shopeasy_tg.arn
  }
}

# Launch Template for EC2 app servers
resource "aws_launch_template" "shopeasy_lt" {
  name_prefix   = "${var.project_name}-app-"
  image_id      = data.aws_ami.amazon_linux_2023.id
  instance_type = "t3.micro"

  iam_instance_profile {
    name = aws_iam_instance_profile.ec2_instance_profile.name
  }

  network_interfaces {
    associate_public_ip_address = false
    security_groups             = [aws_security_group.ec2_sg.id]
  }

  metadata_options {
    http_tokens = "required"
  }

  user_data = base64encode(<<-EOF
    #!/bin/bash
    set -e

    dnf install -y httpd php php-pdo php-mysqlnd git mariadb105
    systemctl enable httpd

    rm -rf /opt/shopeasy
    git clone https://github.com/Habiba1212/shopeasy-database-security-assignment.git /opt/shopeasy

    rm -rf /var/www/html/*
    find /opt/shopeasy -maxdepth 1 -type f -name '*.php' -exec cp {} /var/www/html/ \;

    cat > /var/www/html/index.php <<'PHP'
    <?php http_response_code(200); ?>
    <!doctype html>
    <html>
      <head><title>ShopEasy</title></head>
      <body>
        <h1>ShopEasy Application</h1>
        <p>Application server is healthy.</p>
        <p><a href="/shop.php">Open ShopEasy</a></p>
      </body>
    </html>
    PHP

    sed -i "s/127.0.0.1/shopeasy-rds-mysql.ccl420w8eagx.us-east-1.rds.amazonaws.com/g" /var/www/html/db_connect.php
    chown -R apache:apache /var/www/html
    find /var/www/html -type f -exec chmod 0644 {} \;

    systemctl restart httpd
  EOF
  )

  tag_specifications {
    resource_type = "instance"

    tags = {
      Name = "${var.project_name}-ec2-app-server"
    }
  }
}

# Auto Scaling Group across private app subnets
resource "aws_autoscaling_group" "shopeasy_asg" {
  name                = "${var.project_name}-asg"
  min_size            = 2
  max_size            = 2
  desired_capacity    = 2
  vpc_zone_identifier = [
    aws_subnet.private_app_az1.id,
    aws_subnet.private_app_az2.id
  ]

  target_group_arns = [
    aws_lb_target_group.shopeasy_tg.arn
  ]

  health_check_type         = "ELB"
  health_check_grace_period = 300

  launch_template {
    id      = aws_launch_template.shopeasy_lt.id
    version = "$Latest"
  }

  tag {
    key                 = "Name"
    value               = "${var.project_name}-ec2-app-server"
    propagate_at_launch = true
  }
}
