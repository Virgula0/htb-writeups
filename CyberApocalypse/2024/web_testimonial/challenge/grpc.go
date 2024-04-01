package main

import (
	"context"
	"errors"
	"fmt"
	"htbchal/pb"
	"os"
)

func (s *server) SubmitTestimonial(ctx context.Context, req *pb.TestimonialSubmission) (*pb.GenericReply, error) {
	if req.Customer == "" {
		return nil, errors.New("Name is required")
	}
	if req.Testimonial == "" {
		return nil, errors.New("Content is required")
	}

	// probably todo in this challenge:

	// use the client to overwrite some go files and add an rce
	// sanitization done by the client and not the server

	err := os.WriteFile(fmt.Sprintf("public/testimonials/%s", req.Customer), []byte(req.Testimonial), 0644)
	if err != nil {
		return nil, err
	}

	return &pb.GenericReply{Message: "Testimonial submitted successfully"}, nil
}
