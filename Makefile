image:
	@docker build -t image.goodrain.com/72crm:1.0.2 .
push: image
	@docker push image.goodrain.com/72crm:1.0.2