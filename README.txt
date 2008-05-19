=== Vote-the-Post ===
Tags: voting, feedback, star, statistics, stats, rating
Contributors: alexkingorg
Requires at least: 2.2
Tested up to: 2.5.1
Stable tag: 1.0

Let users vote on the posts at your blog.

== Description ==

Vote-the-Post allows users to rate each post from one to five stars. Vote-the-Post will show the default rating (3 stars = average) unless one user has already voted, in which case the plugin will display their vote. The overall vote calculation includes the default value. The plugin is blogger-friendly. It creates values that are attached to the five star rating system which are fair and friendly.The five star system represents votes of "Below Average, "Needs Work", "Average", "Very Good" and "Excellent". By default, the plugin does not display the number of votes, making the plugin useful for both high traffic and low traffic sites/blogs.    

== Installation == 

1. Download the plugin archive and expand it (you've likely already done this).
2. Upload the Vote-the-Post directory to your wp-content/plugins directory.
3. Go to the Plugins page in your WordPress Administration area and click 'Activate' for Vote-the-Post. This will create the database table used by Vote-the-Post.
4. Congratulations! You've just installed Vote-the-Post and users can now rate your posts.
5. Optional: go into Options > Vote-the-Post to modify the number of times a particular IP address can vote per post.

== Hooks ==

Vote-the-Post provides the "ratepost_vote" hook which is fired each time a user votes.

do_action('ratepost_vote', $post_id, $user_id, $users_vote, $sum_votes, $total_votes);

 $post_id - Post ID
 $user_id - Voter's user ID (0 for anonymous)
 $users_vote - The user's 0 to 5 vote (0 indicates the user removed their vote)
 $sum_votes - sum of all votes
 $total_votes - total number of votes

== Frequently Asked Questions ==

= Why doesn't the plugin calculate and display the "number of votes"? =

Most blogs get well under 10,000 visitors a month and a voting plugin that displays results from 1 and 2 votes throughout a blog prejudices inactive blogs. 

= Why does the plugin incorporate the default rating in the calculation? =

The plugin was designed to give the user 2 forms of feedback that indicates their vote was calculated. First, the user receives a "Thank you for voting" prompt. Second, the user sees an immediate recalculation of the voting result displayed alphanumerically. Initial testing revealed that the first user that voted on every post was confused if their vote was incorporated into the overall results without including the default value, because the real time result would just display their vote. Now, the first user who votes on a post who votes, (e.g., first user votes "5") will see the new result as 4 out of 5 using the default of 3 within the calculation. 

== Funding ==

This plugin was funded by 1800blogger.com
